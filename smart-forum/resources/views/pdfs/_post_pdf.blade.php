@props(['post', 'depth' => 0])

<div class="post" style="margin-left: {{ $depth * 20 }}px; {{ $depth > 0 ? 'border-left: 2px solid #ddd; padding-left: 15px; margin-top: 10px;' : '' }}">
    <div class="author">{{ $post->user->name }}</div>
    <div class="date">{{ $post->created_at->diffForHumans() }}</div>
    <div class="content">{{ $post->content }}</div>
    @if($post->is_private)
        <span class="private-badge">🔒 Private</span>
    @endif
    <span class="likes">❤️ {{ $post->likes_count ?? 0 }}</span>

    @if($post->children->count())
        <div class="replies">
            @foreach($post->children as $child)
                @include('pdfs._post_pdf', ['post' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>