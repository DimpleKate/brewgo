@extends('errors.layout')

@section('title', 'Unauthorized')
@section('code', '401')
@section('icon', 'bi-shield-lock')
@section('heading', 'Please sign in first')
@section('message', 'You need an active account to view this page. Sign in and try again.')
