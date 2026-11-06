@extends('errors.layout')

@section('title', 'Too many requests')
@section('code', '429')
@section('icon', 'bi-hourglass-split')
@section('heading', 'Please slow down')
@section('message', 'Too many requests were sent in a short time. Wait a moment and try again.')
