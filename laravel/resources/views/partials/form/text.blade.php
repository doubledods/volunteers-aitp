<?php

if(old($name))
{
    $value = old($name);
}

?>

@extends('partials/form/_bootstrap')

@section('html')
    <input type="text"
        class="form-control"
        name="{{ $name }}"
        id="{{ $name }}-field"
        placeholder="{{ $placeholder or '' }}"
        value="{{ $value or '' }}"
        @if(isset($autocapitalize))
            autocapitalize="{{ $autocapitalize }}"
        @endif
        @if(isset($limit))
            maxlength="{{ $limit }}"
        @endif
        >
@overwrite
