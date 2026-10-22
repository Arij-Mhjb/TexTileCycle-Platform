<?php
namespace App\Http\Controllers;
use App\Models\BrandNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class BrandNotificationController extends Controller {
 public function read(Request $request,BrandNotification $notification): RedirectResponse {
  abort_unless($notification->user_id===$request->user()->id,403);$notification->update(['read_at'=>now()]);
  return $notification->url?redirect($notification->url):back();
 }
 public function readAll(Request $request): RedirectResponse {
  $request->user()->brandNotifications()->whereNull('read_at')->update(['read_at'=>now()]);return back();
 }
}
