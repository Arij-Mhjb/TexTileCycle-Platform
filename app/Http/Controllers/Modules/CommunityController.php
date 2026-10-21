<?php

namespace App\Http\Controllers\Modules;

use App\Http\Controllers\Controller;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\User;
use App\Services\NotificationCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CommunityController extends Controller
{
    public function index(Request $request): View
    {
        return view('community.index', [
            'posts' => $this->feed($request)->paginate(12)->withQueryString(),
        ]);
    }

    public function profile(Request $request, User $user): View
    {
        abort_unless($user->role === 'consumer', 404);

        return view('community.profile', [
            'member' => $user,
            'posts' => $this->feed($request)->where('user_id', $user->id)->paginate(12),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));
        if (mb_strlen($query) < 2) {
            return response()->json([]);
        }

        return response()->json(User::query()
            ->where('role', 'consumer')
            ->where('id', '!=', $request->user()->id)
            ->where(fn ($builder) => $builder->where('name', 'like', '%'.$query.'%')->orWhere('email', 'like', '%'.$query.'%'))
            ->orderBy('name')->limit(8)->get()
            ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->profile_photo_url]));
    }

    public function store(Request $request, NotificationCenter $notifications): RedirectResponse
    {
        $data = $request->validate([
            'body' => 'nullable|required_without:media|string|max:3000',
            'media' => 'nullable|required_without:body|image|mimes:jpg,jpeg,png,webp|max:5120',
            'mentions' => 'nullable|array|max:20',
            'mentions.*' => 'integer|distinct|exists:users,id',
        ]);
        $post = CommunityPost::create([
            'user_id' => $request->user()->id,
            'body' => $data['body'] ?? null,
            'media_path' => $request->file('media')?->store('community/'.$request->user()->id, 'public'),
        ]);
        $this->syncMentions($post, null, $data['mentions'] ?? [], $request->user(), $notifications);

        return back()->with('success', 'Votre publication est en ligne.');
    }

    public function destroy(Request $request, CommunityPost $post): RedirectResponse
    {
        abort_unless($post->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        if ($post->media_path) Storage::disk('public')->delete($post->media_path);
        $post->delete();

        return back()->with('success', 'Publication supprimée.');
    }

    public function toggleLike(Request $request, CommunityPost $post, NotificationCenter $notifications): RedirectResponse
    {
        $post = $post->original ?: $post;
        $like = $post->likes()->where('user_id', $request->user()->id)->first();
        if ($like) {
            $like->delete();
            return back()->with('success', 'Mention J’aime retirée.');
        }

        $post->likes()->create(['user_id' => $request->user()->id]);
        if ($post->user_id !== $request->user()->id) {
            $notifications->send($post->user_id, 'community_like', 'Votre publication plaît',
                $request->user()->name.' aime votre publication.', route('consumer.community.index').'#post-'.$post->id);
        }

        return back()->with('success', 'Publication aimée.');
    }

    public function comment(Request $request, CommunityPost $post, NotificationCenter $notifications): RedirectResponse
    {
        $post = $post->original ?: $post;
        $data = $request->validate([
            'body' => 'required|string|max:1200',
            'mentions' => 'nullable|array|max:20',
            'mentions.*' => 'integer|distinct|exists:users,id',
        ]);
        $comment = $post->comments()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        $this->syncMentions(null, $comment, $data['mentions'] ?? [], $request->user(), $notifications);

        if ($post->user_id !== $request->user()->id) {
            $notifications->send($post->user_id, 'community_comment', 'Nouveau commentaire',
                $request->user()->name.' a commenté votre publication.', route('consumer.community.index').'#post-'.$post->id);
        }

        return back()->with('success', 'Commentaire publié.');
    }

    public function destroyComment(Request $request, CommunityComment $comment): RedirectResponse
    {
        abort_unless($comment->user_id === $request->user()->id || $request->user()->isAdmin(), 403);
        $comment->delete();

        return back()->with('success', 'Commentaire supprimé.');
    }

    public function repost(Request $request, CommunityPost $post, NotificationCenter $notifications): RedirectResponse
    {
        $post = $post->original ?: $post;
        if ($post->user_id === $request->user()->id) {
            return back()->withErrors(['publication' => 'Vous ne pouvez pas republier votre propre publication.']);
        }
        if ($request->user()->communityPosts()->where('repost_of_id', $post->id)->exists()) {
            return back()->withErrors(['publication' => 'Vous avez déjà republié cette publication.']);
        }

        $request->user()->communityPosts()->create(['repost_of_id' => $post->id]);
        $notifications->send($post->user_id, 'community_repost', 'Votre publication a été repartagée',
            $request->user()->name.' a republié votre contenu.', route('consumer.community.index').'#post-'.$post->id);

        return back()->with('success', 'Publication repartagée avec la communauté.');
    }

    private function feed(Request $request)
    {
        return CommunityPost::query()
            ->with([
                'author', 'mentions', 'comments.author', 'comments.mentions',
                'original' => fn ($query) => $query
                    ->with(['author', 'mentions', 'comments.author', 'comments.mentions'])
                    ->withCount(['likes', 'comments', 'reposts'])
                    ->withExists(['likes as liked_by_me' => fn ($likes) => $likes->where('user_id', $request->user()->id)]),
            ])
            ->withCount(['likes', 'comments', 'reposts'])
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->latest();
    }

    private function syncMentions(?CommunityPost $post, ?CommunityComment $comment, array $requestedIds, User $author, NotificationCenter $notifications): void
    {
        $ids = User::where('role', 'consumer')->whereIn('id', array_unique($requestedIds))->pluck('id');
        if ($post) $post->mentions()->sync($ids);
        if ($comment) $comment->mentions()->sync($ids);

        $url = route('consumer.community.index').'#post-'.($post?->id ?? $comment?->community_post_id);
        $ids->reject(fn (int $id) => $id === $author->id)->each(fn (int $id) =>
            $notifications->send($id, 'community_mention', 'Vous avez été mentionné(e)',
                $author->name.' vous a mentionné(e) dans la communauté.', $url));
    }
}
