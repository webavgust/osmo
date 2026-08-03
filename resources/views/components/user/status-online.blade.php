<span class="
    profile-status
    pull-right
    d-inline-block
    position-absolute
    bg-success
    rounded-circle
  " @if(!empty($size) || !empty($offset))
        style="
            @if(!empty($size)) width: {{$size}}px; height: {{$size}}px; @endif
            @if(!empty($offset)) top: {{$offset}}%; right: {{$offset}}%; @endif
        "
    @endif
></span>
