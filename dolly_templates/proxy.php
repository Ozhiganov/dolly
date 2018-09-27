<div class="topbar">
    <h1 class="main-title"><?=l10n()->proxy?></h1>
</div>
<div class="forms inner">
	<form method='post' action='<?=$_SERVER['PHP_SELF']?>?action=admin_proxy_save' class='form _proxy'>
		<div class='form__row _hdr'><span class='form__hdr _host'>host</span><span class='form__hdr _port'>port</span><span class='form__hdr _user'>user</span><span class='form__hdr _password'>password</span><span class='form__hdr _type'>type</span><span class='form__hdr _tunnel'>tunnel</span></div><?php
foreach($proxy as $id => $row)
 {
?>		<div class='form__row'><?=html::Text('name', "host[$id]", 'value', $row->host).html::Number('name', "port[$id]", 'value', $row->port, 'min', 0, 'max', 65535).html::Text('name', "user[$id]", 'value', $row->user, 'autocomplete', false).html::Text('name', "password[$id]", 'value', $row->password, 'autocomplete', false).(new Select(HTTP::GetClassMeta('proxy_types')))->SetName("type[$id]")->SetSelected($row->type)->Make().html::CheckBox('name', "tunnel[$id]", 'checked', $row->tunnel).html::Hidden('name', "id[$id]", 'value', $id).html::Button('class', 'form__delete_row', 'value', '×', 'title', l10n()->delete)?></div><?php
 }
$id = 0;
?>		<div class='form__row _new'><?=html::Text('name', "host_new[$id]").html::Number('name', "port_new[$id]", 'min', 0, 'max', 65535).html::Text('name', "user_new[$id]", 'autocomplete', false).html::Text('name', "password_new[$id]", 'autocomplete', false).(new Select(HTTP::GetClassMeta('proxy_types')))->SetName("type_new[$id]")->Make().html::CheckBox('name', "tunnel_new[$id]").html::Button('class', 'form__delete_row', 'value', '×', 'title', l10n()->delete)?></div>
		<div class='form__bottom'><?=html::Submit('class', 'msui_button', 'value', l10n()->save).html::Button('class', 'msui_small_button new_proxy', 'value', l10n()->add).html::Hidden('name', '__redirect', 'value', 'admin_proxy')?></div>
	</form>
</div>
<style type='text/css'>
.form{padding: 32px 26px 30px 46px;}
.form__hdr{font-size:13px;letter-spacing:0.1em;text-transform:uppercase;padding:0 0 4px;color:#7f7f7f;display:inline-block;text-indent:8px;margin:0 2px 0 0;}
.form__hdr._host, .form__row input[name^='host']{width:195px;}
.form__hdr._port, .form__row input[name^='port']{width:80px;}
.form__hdr._user, .form__row input[name^='user']{width:140px;}
.form__hdr._password, .form__row input[name^='password']{width:140px;}
.form__hdr._type, .form__row select[name^='type']{width:110px;}
.form__row input[type='checkbox']{padding:0;margin:0 0 0 12px;}
.form__row{margin:0 0 8px;white-space:nowrap;}
.form__row._hdr{white-space:nowrap;}
.form__bottom{padding:11px 0;position:relative;}
.form__row input[type='text'], .form__row input[type='number'], .form__row select{background:#f6f8fa;border:1px solid #dee0e3;border-radius:5px;margin:0 2px 0 0;padding:5px 9px;box-sizing:border-box;}
.form__row select{padding:4px 7px;}
.msui_button{text-align: center;
    font-size: 14px;
    letter-spacing: 0.1em;
    color: #fff;
    border: none;
    border-radius: 4px;
    text-transform: uppercase;
    padding: 8px 20px;
    cursor: pointer;
    background: #68dafe;
    background: -moz-linear-gradient(top, #68dafe 0%, #37c9f7 100%);
    background: -webkit-linear-gradient(top, #68dafe 0%, #37c9f7 100%);
    background: linear-gradient(to bottom, #68dafe 0%, #37c9f7 100%);
    filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#68dafe', endColorstr='#37c9f7', GradientType=0);}
.msui_small_button.new_proxy{position:absolute;top:8px;left:220px;}
.form__delete_row{margin:0 0 0 30px;padding:0;width:24px;height:24px;text-align:center;background:#fee;border:1px solid #faa;border-radius:3px;color:red;}
</style>
<script type='text/javascript'>(function(){
$('.msui_small_button.new_proxy').click(function(){
	var n = $('.form__row._new').first().clone(false).insertBefore(this.parentNode);
	n.find("input[type='text'], input[type='number']").val('');
	n.find("select").find('option:first').prop('selected', true);
	n.find("input[type='checkbox']").prop('checked', false);
	n.find("input[name^='host']").focus();
});
$('.form._proxy').on('click', '.form__delete_row', function(){
	var b = $(this);
	b.prevAll("input[type='text'], input[type='number']").val('');
	b.prevAll("select").find('option:first').prop('selected', true);
	b.prevAll("input[type='checkbox']").prop('checked', false);
});
})();</script>