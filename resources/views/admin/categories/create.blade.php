@extends('layouts.admin')

@section('title', 'New Category')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">New category</h1>
        <p class="admin-page-subtitle text-muted-bp">Add a menu section for products.</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.categories.store') }}" method="POST" enctype="multipart/form-data" data-submit-once>
        @csrf
        @include('admin.categories._form')
    </form>
</div>
@endsection
