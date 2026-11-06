@extends('layouts.admin')

@section('title', 'New Add-on')

@section('content')
<div class="admin-page-head">
    <div>
        <h1 class="admin-page-title">New add-on</h1>
        <p class="admin-page-subtitle text-muted-bp">Create a paid extra that products can offer.</p>
    </div>
</div>

<div class="admin-card admin-form-card">
    <form action="{{ route('admin.addons.store') }}" method="POST" data-submit-once>
        @csrf
        @include('admin.addons._form')
    </form>
</div>
@endsection
