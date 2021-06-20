
@if(!empty($success) && $success)
<div class="ui success message"  style="display: block;">
    <i class="close icon"></i>
    <div class="header"> Success! </div>
    <p>{!! $msg? $msg : 'Operation was successful!' !!}</p>
</div>
@elseif(!empty($errMsg) && $errMsg)
<div class="ui error message" style="display: block;">
    <i class="close icon"></i>
    <div class="header"> <!-- title goes here --> </div>
    <p>{!! $errMsg? $debugMsg : 'Operation was unsuccessful!' !!}</p>
</div>
@endif

