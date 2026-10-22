<?php
namespace App\Services;
use App\Models\ActivityLog;
use App\Models\BrandNotification;
use Illuminate\Database\Eloquent\Model;
class ActivityService {
 public function log(?int $userId,?int $brandId,string $action,?Model $subject=null,array $metadata=[]): ActivityLog {
  return ActivityLog::create(['user_id'=>$userId,'brand_id'=>$brandId,'action'=>$action,'subject_type'=>$subject?->getMorphClass(),'subject_id'=>$subject?->getKey(),'metadata'=>$metadata]);
 }
 public function notify(int $userId,string $type,string $title,string $message,?string $url=null): BrandNotification {
  return BrandNotification::create(['user_id'=>$userId,'type'=>$type,'title'=>$title,'message'=>$message,'url'=>$url]);
 }
}
