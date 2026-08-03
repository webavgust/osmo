@extends('layouts.layout')

@section('styles')
@endsection

@section('content')
        <div class="container-fluid">
            <div class="row">
                <!-- Column -->
                <div class="col-lg-4 col-xlg-3 col-md-5">
                    <div class="card">
                        <div class="card-body">
                            <center class="mt-4">
                                <span class="position-relative d-inline-block">
                                    <img
                                        src="{{ $user->avatar(300) }}"
                                        class="rounded-circle"
                                        width="150"
                                    />
                                    @if($user->isOnline)
                                        <x-user.status-online size="16" offset="10"></x-user.status-online>
                                    @endif
                                </span>


                                <h4 class="card-title mt-2">{{ $user->fullName }}</h4>
                                <h6 class="card-subtitle">
                                    <div class="fs-3 mb-1 mt-1 ps-1 pe-1 badge bg-light text-dark me-2">{{ $user->work_department }}</div>
                                    <div class="fs-3 mb-1 badge font-weight-medium bg-light-primary text-primary ">{{ $user->work_position }}</div>
                                </h6>

                            </center>
                        </div>
                        <div>
                            <hr />
                        </div>
                        <div class="card-body">
                            <small class="text-muted">Эл.почта: </small>
                            <h6>{{ $user->email }}</h6>

                            @if(!empty($user->work_phone))
                                <small class="text-muted pt-4 db">Внут. номер</small>
                                <h6>{{ $user->work_phone }}</h6>
                            @endif

                            @if(!empty($user->personal_birthday))
                                <small class="text-muted pt-4 db">День рождения</small>
                                <h6>{{ _date($user->personal_birthday) }}</h6>
                            @endif

                            @if(is_admin())
                                <small class="text-muted pt-1 d-inline-block">Группы</small>
                                <div>
                                    @forelse($user->groups as $group)
                                        <x-ui.a href="{{ route('user_group.detail', $group) }}">
                                            <x-ui.badge.default type="primary">{{ $group->name }}</x-ui.badge.default>
                                        </x-ui.a>
                                    @empty
                                        Нет назначенных групп
                                    @endforelse
                                </div>

                                <small class="text-muted pt-1 mt-2 d-inline-block">Подразделения</small>
                                <div>
                                    @forelse($user->departments as $department)
                                        <x-ui.a href="{{ route('user_department.detail', $department) }}">
                                            <x-ui.badge.default type="info">{{ $department->name }}</x-ui.badge.default>
                                        </x-ui.a>
                                    @empty
                                        Нет назначенных подразделений
                                    @endforelse
                                </div>
                            @endif
                        </div>

                    </div>
                </div>
                <!-- Column -->
                <div class="col-lg-4 col-xlg-9 col-md-7">


                    <div class="card users">
                        <div class="card-body bg-light-secondary rounded-top d-flex justify-content-between p-2 align-items-center">
                            <h4 class=" card-title m-0 ms-2">Руководители</h4>
                            @can('users_sub_users_control')
                                <x-ui.button.sidebar btn_type="secondary" href="{{  route('users.sidebar_sub_users_parent', $user) }}">Управлять</x-ui.button.sidebar>
                            @endcan
                        </div>
                        <div class="message-box contact-box position-relative">
                            <div class="message-widget contact-widget position-relative">
                                <x-user.detail-sub-user-block block="parent" :subUsers="$parent_users"></x-user.detail-sub-user-block>
                            </div>
                        </div>
                    </div>

                    <div class="card users">
                        <div class="card-body bg-light-secondary rounded-top d-flex justify-content-between p-2 align-items-center">
                            <h4 class=" card-title m-0 ms-2">Подчинённые</h4>
                            @can('users_sub_users_control')
                                <x-ui.button.sidebar btn_type="secondary" href="{{  route('users.sidebar_sub_users_sub', $user) }}">Управлять</x-ui.button.sidebar>
                            @endcan
                        </div>
                        <div class="message-box contact-box position-relative">
                            <div class="message-widget contact-widget position-relative">
                                <x-user.detail-sub-user-block block="sub" :subUsers="$sub_users"></x-user.detail-sub-user-block>
                            </div>
                        </div>
                    </div>



                </div>
                <div class="col-lg-4 col-xlg-9 col-md-7 d-none">
                    <div class="card">
                        <!-- Tabs -->
                        <ul
                            class="nav nav-pills custom-pills"
                            id="pills-tab"
                            role="tablist"
                        >
                            <li class="nav-item">
                                <a
                                    class="nav-link active"
                                    id="pills-timeline-tab"
                                    data-bs-toggle="pill"
                                    href="#current-month"
                                    role="tab"
                                    aria-controls="pills-timeline"
                                    aria-selected="true"
                                >Timeline</a
                                >
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    id="pills-profile-tab"
                                    data-bs-toggle="pill"
                                    href="#last-month"
                                    role="tab"
                                    aria-controls="pills-profile"
                                    aria-selected="false"
                                >Profile</a
                                >
                            </li>
                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    id="pills-setting-tab"
                                    data-bs-toggle="pill"
                                    href="#previous-month"
                                    role="tab"
                                    aria-controls="pills-setting"
                                    aria-selected="false"
                                >Setting</a
                                >
                            </li>
                        </ul>
                        <!-- Tabs -->
                        <div class="tab-content" id="pills-tabContent">
                            <div
                                class="tab-pane fade show active"
                                id="current-month"
                                role="tabpanel"
                                aria-labelledby="pills-timeline-tab"
                            >
                                <div class="card-body">
                                    <div class="profiletimeline mt-0">
                                        <div class="sl-item d-flex align-items-start">
                                            <div class="sl-left">
                                                <img
                                                    src="../../assets/images/users/1.jpg"
                                                    alt="user"
                                                    class="rounded-circle"
                                                />
                                            </div>
                                            <div class="sl-right">
                                                <div>
                                                    <a href="javascript:void(0)" class="link"
                                                    >John Doe</a
                                                    >
                                                    <span class="sl-date">5 minutes ago</span>
                                                    <p>
                                                        assign a new task
                                                        <a href="javascript:void(0)">
                                                            Design weblayout</a
                                                        >
                                                    </p>
                                                    <div class="row">
                                                        <div class="col-lg-3 col-md-6 mb-3">
                                                            <img
                                                                src="../../assets/images/big/img1.jpg"
                                                                class="img-fluid rounded"
                                                            />
                                                        </div>
                                                        <div class="col-lg-3 col-md-6 mb-3">
                                                            <img
                                                                src="../../assets/images/big/img2.jpg"
                                                                class="img-fluid rounded"
                                                            />
                                                        </div>
                                                        <div class="col-lg-3 col-md-6 mb-3">
                                                            <img
                                                                src="../../assets/images/big/img3.jpg"
                                                                class="img-fluid rounded"
                                                            />
                                                        </div>
                                                        <div class="col-lg-3 col-md-6 mb-3">
                                                            <img
                                                                src="../../assets/images/big/img4.jpg"
                                                                class="img-fluid rounded"
                                                            />
                                                        </div>
                                                    </div>
                                                    <div class="like-comm">
                                                        <a href="javascript:void(0)" class="link me-2"
                                                        >2 comment</a
                                                        >
                                                        <a href="javascript:void(0)" class="link me-2"
                                                        ><i class="fa fa-heart text-danger"></i> 5
                                                            Love</a
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="sl-item d-flex align-items-start">
                                            <div class="sl-left">
                                                <img
                                                    src="../../assets/images/users/2.jpg"
                                                    alt="user"
                                                    class="rounded-circle"
                                                />
                                            </div>
                                            <div class="sl-right">
                                                <div>
                                                    <a href="javascript:void(0)" class="link"
                                                    >John Doe</a
                                                    >
                                                    <span class="sl-date">5 minutes ago</span>
                                                    <div class="mt-3 row">
                                                        <div class="col-md-3 col-xs-12">
                                                            <img
                                                                src="../../assets/images/big/img1.jpg"
                                                                alt="user"
                                                                class="img-fluid rounded"
                                                            />
                                                        </div>
                                                        <div class="col-md-9 col-xs-12">
                                                            <p>
                                                                Lorem ipsum dolor sit amet, consectetur
                                                                adipiscing elit. Integer nec odio. Praesent
                                                                libero. Sed cursus ante dapibus diam.
                                                            </p>
                                                            <a
                                                                href="javascript:void(0)"
                                                                class="btn btn-success"
                                                            >
                                                                Design weblayout</a
                                                            >
                                                        </div>
                                                    </div>
                                                    <div class="like-comm mt-3">
                                                        <a href="javascript:void(0)" class="link me-2"
                                                        >2 comment</a
                                                        >
                                                        <a href="javascript:void(0)" class="link me-2"
                                                        ><i class="fa fa-heart text-danger"></i> 5
                                                            Love</a
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="sl-item d-flex align-items-start">
                                            <div class="sl-left">
                                                <img
                                                    src="../../assets/images/users/3.jpg"
                                                    alt="user"
                                                    class="rounded-circle"
                                                />
                                            </div>
                                            <div class="sl-right">
                                                <div>
                                                    <a href="javascript:void(0)" class="link"
                                                    >John Doe</a
                                                    >
                                                    <span class="sl-date">5 minutes ago</span>
                                                    <p class="mt-2">
                                                        Lorem ipsum dolor sit amet, consectetur
                                                        adipiscing elit. Integer nec odio. Praesent
                                                        libero. Sed cursus ante dapibus diam. Sed nisi.
                                                        Nulla quis sem at nibh elementum imperdiet. Duis
                                                        sagittis ipsum. Praesent mauris. Fusce nec
                                                        tellus sed augue semper
                                                    </p>
                                                </div>
                                                <div class="like-comm mt-3">
                                                    <a href="javascript:void(0)" class="link me-2"
                                                    >2 comment</a
                                                    >
                                                    <a href="javascript:void(0)" class="link me-2"
                                                    ><i class="fa fa-heart text-danger"></i> 5
                                                        Love</a
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                        <hr />
                                        <div class="sl-item d-flex align-items-start">
                                            <div class="sl-left">
                                                <img
                                                    src="../../assets/images/users/4.jpg"
                                                    alt="user"
                                                    class="rounded-circle"
                                                />
                                            </div>
                                            <div class="sl-right">
                                                <div>
                                                    <a href="javascript:void(0)" class="link"
                                                    >John Doe</a
                                                    >
                                                    <span class="sl-date">5 minutes ago</span>
                                                    <blockquote class="mt-2">
                                                        Lorem ipsum dolor sit amet, consectetur
                                                        adipisicing elit, sed do eiusmod tempor
                                                        incididunt
                                                    </blockquote>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="tab-pane fade"
                                id="last-month"
                                role="tabpanel"
                                aria-labelledby="pills-profile-tab"
                            >
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 col-xs-6 b-r">
                                            <strong>Full Name</strong>
                                            <br />
                                            <p class="text-muted">Johnathan Deo</p>
                                        </div>
                                        <div class="col-md-3 col-xs-6 b-r">
                                            <strong>Mobile</strong>
                                            <br />
                                            <p class="text-muted">(123) 456 7890</p>
                                        </div>
                                        <div class="col-md-3 col-xs-6 b-r">
                                            <strong>Email</strong>
                                            <br />
                                            <p class="text-muted">johnathan@admin.com</p>
                                        </div>
                                        <div class="col-md-3 col-xs-6">
                                            <strong>Location</strong>
                                            <br />
                                            <p class="text-muted">London</p>
                                        </div>
                                    </div>
                                    <hr />
                                    <p class="mt-4">
                                        Donec pede justo, fringilla vel, aliquet nec, vulputate
                                        eget, arcu. In enim justo, rhoncus ut, imperdiet a,
                                        venenatis vitae, justo. Nullam dictum felis eu pede
                                        mollis pretium. Integer tincidunt.Cras dapibus. Vivamus
                                        elementum semper nisi. Aenean vulputate eleifend tellus.
                                        Aenean leo ligula, porttitor eu, consequat vitae,
                                        eleifend ac, enim.
                                    </p>
                                    <p>
                                        Lorem Ipsum is simply dummy text of the printing and
                                        typesetting industry. Lorem Ipsum has been the
                                        industry's standard dummy text ever since the 1500s,
                                        when an unknown printer took a galley of type and
                                        scrambled it to make a type specimen book. It has
                                        survived not only five centuries
                                    </p>
                                    <p>
                                        It was popularised in the 1960s with the release of
                                        Letraset sheets containing Lorem Ipsum passages, and
                                        more recently with desktop publishing software like
                                        Aldus PageMaker including versions of Lorem Ipsum.
                                    </p>
                                    <h4 class="font-weight-medium mt-4">Skill Set</h4>
                                    <hr />
                                    <h5 class="mt-4">
                                        Wordpress <span class="pull-right">80%</span>
                                    </h5>
                                    <div class="progress">
                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            aria-valuenow="80"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: 80%; height: 6px"
                                        >
                                            <span class="sr-only">50% Complete</span>
                                        </div>
                                    </div>
                                    <h5 class="mt-4">
                                        HTML 5 <span class="pull-right">90%</span>
                                    </h5>
                                    <div class="progress">
                                        <div
                                            class="progress-bar bg-info"
                                            role="progressbar"
                                            aria-valuenow="90"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: 90%; height: 6px"
                                        >
                                            <span class="sr-only">50% Complete</span>
                                        </div>
                                    </div>
                                    <h5 class="mt-4">
                                        jQuery <span class="pull-right">50%</span>
                                    </h5>
                                    <div class="progress">
                                        <div
                                            class="progress-bar bg-danger"
                                            role="progressbar"
                                            aria-valuenow="50"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: 50%; height: 6px"
                                        >
                                            <span class="sr-only">50% Complete</span>
                                        </div>
                                    </div>
                                    <h5 class="mt-4">
                                        Photoshop <span class="pull-right">70%</span>
                                    </h5>
                                    <div class="progress">
                                        <div
                                            class="progress-bar bg-warning"
                                            role="progressbar"
                                            aria-valuenow="70"
                                            aria-valuemin="0"
                                            aria-valuemax="100"
                                            style="width: 70%; height: 6px"
                                        >
                                            <span class="sr-only">50% Complete</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="tab-pane fade"
                                id="previous-month"
                                role="tabpanel"
                                aria-labelledby="pills-setting-tab"
                            >
                                <div class="card-body">
                                    <form class="form-horizontal form-material">
                                        <div class="mb-3">
                                            <label class="col-md-12">Full Name</label>
                                            <div class="col-md-12">
                                                <input
                                                    type="text"
                                                    placeholder="Johnathan Doe"
                                                    class="form-control form-control-line"
                                                />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="example-email" class="col-md-12"
                                            >Email</label
                                            >
                                            <div class="col-md-12">
                                                <input
                                                    type="email"
                                                    placeholder="johnathan@admin.com"
                                                    class="form-control form-control-line"
                                                    name="example-email"
                                                    id="example-email"
                                                />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="col-md-12">Password</label>
                                            <div class="col-md-12">
                                                <input
                                                    type="password"
                                                    value="password"
                                                    class="form-control form-control-line"
                                                />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="col-md-12">Phone No</label>
                                            <div class="col-md-12">
                                                <input
                                                    type="text"
                                                    placeholder="123 456 7890"
                                                    class="form-control form-control-line"
                                                />
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="col-md-12">Message</label>
                                            <div class="col-md-12">
                            <textarea
                                rows="5"
                                class="form-control form-control-line"
                            ></textarea>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="col-sm-12">Select Country</label>
                                            <div class="col-sm-12">
                                                <select class="form-control form-control-line">
                                                    <option>London</option>
                                                    <option>India</option>
                                                    <option>Usa</option>
                                                    <option>Canada</option>
                                                    <option>Thailand</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="col-sm-12">
                                                <button class="btn btn-success">
                                                    Update Profile
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Column -->
            </div>
        </div>



@endsection

@section('js')
    @parent
@endsection
