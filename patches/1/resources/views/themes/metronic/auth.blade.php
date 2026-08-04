@extends('layouts.layout_short')
@section('title',  __('auth.title'))

@section('body')
    <div class="d-flex flex-column flex-root" id="kt_app_root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">

            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-100 w-md-400px">
                        <form class="form w-100" method="post" action="{{ route('auth.form', ['back' => $back]) }}">
                            @csrf

                            <div class="text-center mb-11">
                                <img src="/images/logo/logo_letter.svg" alt="OSMO" class="h-60px mb-5" />
                                <h1 class="text-gray-900 fw-bolder mb-3">{{ __('auth.block_title') }}</h1>
                            </div>

                            <div class="fv-row mb-8">
                                <input type="text" name="login" value="{{ old('login') }}" required
                                       placeholder="{{ __('auth.login.placeholder') }}"
                                       autocomplete="off" class="form-control bg-transparent" />
                            </div>

                            <div class="fv-row mb-3">
                                <input type="password" name="password" required
                                       placeholder="{{ __('auth.password.placeholder') }}"
                                       autocomplete="off" class="form-control bg-transparent" />
                            </div>

                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="remember" value="1" @if(old('remember')) checked @endif />
                                    <span class="form-check-label text-gray-700">{{ __('auth.remember_me') }}</span>
                                </label>
                            </div>

                            <div class="d-grid mb-10">
                                <button type="submit" class="btn btn-primary">{{ __('auth.button.login') }}</button>
                            </div>

                            @if(Session::has('access_denied'))
                                <div class="alert alert-danger">
                                    <strong>{{ __('auth.error_caption') }} — </strong> {{ Session::get('access_denied') }}
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger">
                                    <strong>{{ __('auth.error_caption') }} — </strong> {{ $errors->first() }}
                                </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
                 style="background-color: #0d1117; background-image: url('/assets/images/big/auth-bg.jpg'); background-size: cover; background-position: center;">
            </div>

        </div>
    </div>
@endsection
