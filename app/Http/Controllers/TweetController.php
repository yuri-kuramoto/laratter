<?php

namespace App\Http\Controllers;

use App\Models\Tweet;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TweetController extends Controller
{
    /**
     * 一覧表示
     */
    public function index()
    {
        $now = Carbon::now(); // 日本時間を前提（.env の timezone が Asia/Tokyo なら自動）

        // scheduled_at が null または現在時刻を過ぎた投稿だけ取得
        $tweets = Tweet::with(['user', 'liked'])
            ->where(function($query) use ($now) {
                $query->whereNull('scheduled_at')
                      ->orWhere('scheduled_at', '<=', $now);
            })
            ->latest()
            ->get();

        return view('tweets.index', compact('tweets'));
    }

    /**
     * 新規投稿フォーム
     */
    public function create()
    {
        return view('tweets.create');
    }

    /**
     * 投稿保存
     */
    public function store(Request $request)
    {
        $request->validate([
            'tweet' => 'required|max:255',
            'scheduled_at' => 'nullable|date',
        ]);

        $data = $request->only('tweet', 'scheduled_at');

        // scheduled_at が入力されていれば日本時間で保存
        if (!empty($data['scheduled_at'])) {
            $data['scheduled_at'] = Carbon::parse($data['scheduled_at'], config('app.timezone'));
        }

        $request->user()->tweets()->create($data);

        return redirect()->route('tweets.index');
    }

    /**
     * 投稿詳細
     */
    public function show(Tweet $tweet)
    {
        $now = Carbon::now();

        // scheduled_at が未来なら 404
        if ($tweet->scheduled_at && $tweet->scheduled_at->isFuture()) {
            abort(404);
        }

        $tweet->load('comments', 'liked', 'user');

        return view('tweets.show', compact('tweet'));
    }

    /**
     * 投稿編集フォーム
     */
    public function edit(Tweet $tweet)
    {
        return view('tweets.edit', compact('tweet'));
    }

    /**
     * 投稿更新
     */
    public function update(Request $request, Tweet $tweet)
    {
        $request->validate([
            'tweet' => 'required|max:255',
            'scheduled_at' => 'nullable|date',
        ]);

        $data = $request->only('tweet', 'scheduled_at');

        if (!empty($data['scheduled_at'])) {
            $data['scheduled_at'] = Carbon::parse($data['scheduled_at'], config('app.timezone'));
        }

        $tweet->update($data);

        return redirect()->route('tweets.show', $tweet);
    }

    /**
     * 投稿削除
     */
    public function destroy(Tweet $tweet)
    {
        $tweet->delete();
        return redirect()->route('tweets.index');
    }

    /**
     * キーワード検索
     */
    public function search(Request $request)
{
    $now = Carbon::now()->setTimezone(config('app.timezone')); // 現在時刻取得

    $query = Tweet::query();

    if ($request->filled('keyword')) {
        $keyword = $request->keyword;
        $query->where('tweet', 'like', '%' . $keyword . '%');
    }

    // scheduled_at が null または現在時刻を過ぎた投稿だけ取得
    $query->where(function($q) use ($now) {
        $q->whereNull('scheduled_at')
          ->orWhere('scheduled_at', '<=', $now);
    });

    $tweets = $query->latest()->paginate(10);

    return view('tweets.search', compact('tweets'));
}


    /**
     * いいね機能
     */
    public function like(Tweet $tweet)
    {
        $tweet->liked()->attach(auth()->id());
        return back();
    }

    /**
     * いいね解除
     */
    public function dislike(Tweet $tweet)
    {
        $tweet->liked()->detach(auth()->id());
        return back();
    }
}
