<?php
namespace App\Http\Controllers;
use App\Models\Evidence;use Illuminate\Http\Request;use Illuminate\Support\Facades\Storage;
class EvidenceDownloadController extends Controller {
 public function __invoke(Request $request,Evidence $evidence) {
  $allowed=$request->user()->isAdmin()||($request->user()->isBrand()&&$evidence->assessment->user_id===$request->user()->id);
  abort_unless($allowed,403);abort_unless($evidence->file_path&&Storage::disk('local')->exists($evidence->file_path),404);
  return Storage::disk('local')->download($evidence->file_path,basename($evidence->file_path));
 }
}
