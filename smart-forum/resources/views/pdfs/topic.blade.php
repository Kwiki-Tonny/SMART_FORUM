<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $topic->title }} - PDF</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; margin: 20px; }
        h1 { font-size: 24px; border-bottom: 2px solid #075E54; padding-bottom: 10px; }
        .meta { font-size: 14px; color: #555; margin-bottom: 20px; }
        .post { margin-bottom: 15px; padding: 10px; background: #f9f9f9; border-radius: 5px; }
        .post .author { font-weight: bold; color: #075E54; }
        .post .date { font-size: 12px; color: #888; float: right; }
        .post .content { margin-top: 5px; }
        .post .replies { margin-left: 20px; border-left: 2px solid #ddd; padding-left: 15px; margin-top: 10px; }
        .private-badge { color: #e67e22; font-size: 12px; }
        .footer { margin-top: 30px; font-size: 12px; color: #aaa; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
        .likes { font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <h1>{{ $topic->title }}</h1>
    <div class="meta">
        <strong>Group:</strong> {{ $topic->group->name }}<br>
        <strong>Author:</strong> {{ $topic->creator->name }}<br>
        <strong>Posted:</strong> {{ $topic->created_at->format('F j, Y \a\t g:i A') }}<br>
        <span class="likes">❤️ {{ $topic->likes_count }} likes &nbsp;|&nbsp; 👁️ {{ $topic->views_count }} views &nbsp;|&nbsp; 💬 {{ $topic->comments_count }} comments</span>
    </div>

    <div class="post" style="background: #eef;">
        <div class="content">{{ $topic->body }}</div>
        <div class="author">{{ $topic->creator->name }}</div>
        <div class="date">{{ $topic->created_at->diffForHumans() }}</div>
    </div>

    <h2 style="font-size:18px; margin-top:20px;">Replies</h2>

    @foreach($topic->posts()->whereNull('parent_id')->with('user', 'children.user')->oldest()->get() as $post)
        @include('pdfs._post_pdf', ['post' => $post, 'depth' => 0])
    @endforeach

    <div class="footer">
        Generated on {{ now()->format('F j, Y \a\t g:i A') }} &bull; Smart Discussion Forum
    </div>
</body>
</html>