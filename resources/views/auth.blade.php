@extends('layouts.layout_short')
@section('title',  __('auth.title'))
@section('body')
    <div class="main-wrapper">
        <!-- -------------------------------------------------------------- -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- -------------------------------------------------------------- -->
        <div class="preloader">
            <svg class="tea lds-ripple" width="37" height="48" viewbox="0 0 37 48" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M27.0819 17H3.02508C1.91076 17 1.01376 17.9059 1.0485 19.0197C1.15761 22.5177 1.49703 29.7374 2.5 34C4.07125 40.6778 7.18553 44.8868 8.44856 46.3845C8.79051 46.79 9.29799 47 9.82843 47H20.0218C20.639 47 21.2193 46.7159 21.5659 46.2052C22.6765 44.5687 25.2312 40.4282 27.5 34C28.9757 29.8188 29.084 22.4043 29.0441 18.9156C29.0319 17.8436 28.1539 17 27.0819 17Z" stroke="#1e88e5" stroke-width="2"></path><path d="M29 23.5C29 23.5 34.5 20.5 35.5 25.4999C36.0986 28.4926 34.2033 31.5383 32 32.8713C29.4555 34.4108 28 34 28 34" stroke="#1e88e5" stroke-width="2"></path><path id="teabag" fill="#1e88e5" fill-rule="evenodd" clip-rule="evenodd" d="M16 25V17H14V25H12C10.3431 25 9 26.3431 9 28V34C9 35.6569 10.3431 37 12 37H18C19.6569 37 21 35.6569 21 34V28C21 26.3431 19.6569 25 18 25H16ZM11 28C11 27.4477 11.4477 27 12 27H18C18.5523 27 19 27.4477 19 28V34C19 34.5523 18.5523 35 18 35H12C11.4477 35 11 34.5523 11 34V28Z"></path><path id="steamL" d="M17 1C17 1 17 4.5 14 6.5C11 8.5 11 12 11 12" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" stroke="#1e88e5"></path><path id="steamR" d="M21 6C21 6 21 8.22727 19 9.5C17 10.7727 17 13 17 13" stroke="#1e88e5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path></svg>
        </div>
        <div class=" auth-wrapper d-flex no-block justify-content-center align-items-center "  style="
          background: url(/assets/images/big/auth-bg.jpg) no-repeat center
            center;
        ">
            <div class="auth-box p-4 bg-white rounded">
                <div id="loginform">
                    <div class="logo">
                        <h3 class="box-title mb-3">{{ __('auth.block_title') }}</h3>
                    </div>
                    <!-- Form -->
                    <div class="row">
                        <div class="col-12">
                            <form
                                class="form-horizontal mt-3 form-material"
                                id="loginform"
                                method="post"
                                action="{{ route('auth.form', ['back' => $back]) }}"
                            >
                                @csrf
                                <div class="form-group mb-3">
                                    <div class="">
                                        <input
                                            class="form-control"
                                            type="text"
                                            required=""
                                            placeholder="{{ __('auth.login.placeholder') }}"
                                            name="login"
                                            value="{{ old('login') }}"
                                        />
                                    </div>
                                </div>
                                <div class="form-group mb-4">
                                    <div class="">
                                        <input
                                            class="form-control"
                                            type="password"
                                            required=""
                                            placeholder="{{ __('auth.password.placeholder') }}"
                                            name="password"
                                        />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <div class="d-flex">
                                        <div class="checkbox checkbox-info pt-0">
                                            <input
                                                id="checkbox-signup"
                                                type="checkbox"
                                                class="material-inputs chk-col-indigo"
                                                name="remember"
                                                value="1"
                                                @if(old('remember')) checked="Y" @endif
                                            />
                                            <label for="checkbox-signup">{{ __('auth.remember_me') }}</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group text-center mt-4 mb-3">
                                    <div class="col-xs-12">
                                        <button
                                            class="
                                              btn btn-info
                                              d-block
                                              w-100
                                              waves-effect waves-light
                                            "
                                            type="submit"
                                        >
                                            {{ __('auth.button.login') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                            @if(Session::has('access_denied'))
                                <div class="alert alert-danger" role="alert">
                                    <strong>{{ __('auth.error_caption') }} - </strong> {{ Session::get('access_denied') }}
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <strong>{{ __('auth.error_caption') }} - </strong> {{$errors->first()}}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection
