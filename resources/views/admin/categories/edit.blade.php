@extends('layouts.admin')

@section('title', 'Edit '.$category->name)

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">Edit category</h1>
        <p class="admin-page-subtitle text-muted-bp">{{ $category->name }}</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @method('PUT')
        @include('admin.categories._form', ['category' => $category])
    </form>
</div>
@endsection
