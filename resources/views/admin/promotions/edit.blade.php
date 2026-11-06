@extends('layouts.admin')

@section('title', 'Edit '.$promotion->title)

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Edit promotion</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $promotion->title }}</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.promotions.update', $promotion) }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @method('PUT')
        @include('admin.promotions._form', ['promotion' => $promotion])
    </form>
</div>
@endsection
