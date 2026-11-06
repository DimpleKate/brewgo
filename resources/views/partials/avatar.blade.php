@php
    $avatarUser = $user ?? auth()->user();
    $avatarSize = $size ?? 'md';
    $avatarExtraClass = $class ?? '';
    $avatarUrl = $avatarUser?->avatarUrl();
    $avatarInitial = $avatarUser?->avatarInitial() ?? '?';
@endphp

<span class="avatar-circle avatar-{{ $avatarSize }} {{ $avatarExtraClass }}">
    @if($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $avatarUser->name }} profile picture">
    @else
        <span role="img" aria-label="{{ $avatarUser?->name ?? 'Brew & Go user' }} profile picture" aria-hidden="true">{{ $avatarInitial }}</span>
    @endif
</span>
