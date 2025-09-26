<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tweet extends Model
{
    /** @use HasFactory<\Database\Factories\TweetFactory> */
    use HasFactory;
 
    use HasFactory;

  protected $fillable = ['tweet'];
  
  //一対多の連携の設定　自分が多
  public function user()
  {
    return $this->belongsTo(User::class);
  }
  public function liked()
  {
      return $this->belongsToMany(User::class)->withTimestamps();
  }
}
