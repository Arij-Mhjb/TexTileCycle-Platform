<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyseBrandApplication;
use App\Models\Brand;
use App\Models\BrandApplication;
use App\Models\Label;
use App\Notifications\BrandApplicationMail;
use App\Services\ActivityService;
use App\Services\BrandQuestionnaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class AdminBrandApplicationController extends Controller
{
    public function index()
    {
        return view('admin.brand-applications.index', ['applications' => BrandApplication::with('brand.user')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()->paginate(20)]);
    }

    public function show(BrandApplication $application, BrandQuestionnaire $questionnaire)
    {
        return view('admin.brand-applications.show', ['application' => $application->load('brand.user', 'reviewer'),
            'questions' => $questionnaire->questions(), 'labels' => Label::where('is_active', true)->orderBy('name')->get()]);
    }

    public function analyse(BrandApplication $application)
    {
        abort_unless($application->status === 'pending', 409);
        abort_unless(in_array($application->analysis_status, ['unavailable', 'failed', 'completed']), 409);
        $application->update(['analysis_status' => 'pending', 'analysis_summary' => null, 'recommendations' => null, 'analysed_at' => null]);
        AnalyseBrandApplication::dispatch($application->id);

        return back()->with('success', 'Analyse IA programmée.');
    }

    public function approve(Request $request, BrandApplication $application)
    {
        // HTML forms omit an unchecked checkbox group; preserve an explicit empty selection.
        if ($request->input('selection_submitted') === '1' && ! $request->has('labels')) {
            $request->merge(['labels' => []]);
        }
        $data = $request->validate([
            'labels' => 'present|array|max:30',
            'labels.*' => ['integer', 'distinct', Rule::exists('labels', 'id')->where('is_active', true)],
            'review_note' => 'required|string|max:5000',
        ]);
        DB::transaction(function () use ($request, $application, $data) {
            $application = BrandApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            abort_unless($application->status === 'pending', 409);
            abort_if($application->analysis_status === 'pending', 409, 'Attendez la fin de l’analyse avant de valider le dossier.');
            $brand = Brand::whereKey($application->brand_id)->lockForUpdate()->firstOrFail();
            $labels = Label::whereIn('id', $data['labels'])->get(['id', 'name', 'official_url']);
            $brand->approvedLabels()->sync($data['labels']);
            $application->update(['status' => 'approved', 'reviewed_by' => $request->user()->id,
                'review_note' => $data['review_note'], 'approved_label_snapshot' => $labels->toArray(), 'reviewed_at' => now()]);
            $brand->update(['onboarding_status' => 'approved', 'is_active' => true]);
            app(ActivityService::class)->log($request->user()->id, $brand->id, 'brand.application.approved', $application,
                ['labels' => $labels->toArray(), 'review_note' => $data['review_note']]);
            $brand->user->notify(new BrandApplicationMail('Votre compte marque est prêt', [
                'Votre compte '.$brand->name.' a été activé. Vous pouvez maintenant vous connecter.',
                'Démarches retenues : '.($labels->pluck('name')->join(', ') ?: 'Aucune démarche retenue à ce stade.'),
                'Commentaire de l’administrateur : '.$data['review_note'],
                'Cette orientation ne constitue pas une certification. Les organismes officiels vérifient les critères et les preuves.',
            ], route('login'), 'Se connecter'));
            $audit = ['Marque : '.$brand->name, 'Contact : '.$brand->user->email,
                'Dossier #'.$application->id.' approuvé par '.$request->user()->email.' le '.$application->reviewed_at->toIso8601String(),
                'Analyse IA : '.$application->analysis_status.' / '.($application->analysis_model ?: 'non disponible'),
                'Résumé IA : '.($application->analysis_summary ?: 'Aucun résumé'),
                'Labels retenus : '.($labels->pluck('name')->join(', ') ?: 'Aucun'), 'Décision : '.$data['review_note']];
            foreach ($application->answers as $key => $value) {
                $audit[] = (app(BrandQuestionnaire::class)->questions()[$key]['label'] ?? $key).' : '.$value;
            }
            foreach ($application->recommendations ?? [] as $recommendation) {
                $audit[] = 'Piste IA '.$recommendation['label_name'].' ('.$recommendation['path'].') : '.$recommendation['reason'];
                $audit[] = 'Écarts : '.implode('; ', $recommendation['gaps']).' — Actions : '.implode('; ', $recommendation['next_steps']);
            }
            Notification::route('mail', config('brand_onboarding.audit_email'))->notify(new BrandApplicationMail(
                'Traçabilité — compte marque approuvé : '.$brand->name, $audit,
                route('admin.brand-applications.show', $application), 'Consulter le dossier'));
        });

        return to_route('admin.brand-applications.show', $application)->with('success', 'Compte activé. Les emails sont programmés.');
    }
}
