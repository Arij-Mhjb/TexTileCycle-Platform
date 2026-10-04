<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CollectionPoint extends Model {
    use HasFactory;
    protected $fillable = ['recovery_program_id','name','address','city','latitude','longitude','opening_hours','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean','latitude'=>'decimal:7','longitude'=>'decimal:7']; }
    public function recoveryProgram(): BelongsTo { return $this->belongsTo(RecoveryProgram::class); }
    public function returns(): HasMany { return $this->hasMany(ProductReturn::class); }
}
