<?php
trait TNotificationsViewer
{
	protected function ShowNotifications($res, $viewed = false)
	 {
		$this->AddCSS('lib.msnotificationsviewer')->AddJS('lib.msnotificationsviewer');
?><div class='msnotifications' data-viewed='<?=$viewed ? 'true' : 'false'?>'><?php
		if($viewed) echo html::CheckBox('class', 'msnotifications__check_all');
		foreach($res as $n) : 
?><div class='msnotification' data-id='<?=$n->id?>'<?=$n->viewed_at ? " data-viewed_at='$n->viewed_at'" : ''?>>
	<div class='msnotification__subject'><?=$n->subject?><time class='msnotification__time _sent_at' datetime='<?=$n->sent_at?>'><?=Format::AsDateTime($n->sent_at)?></time></div>
	<div class='msnotification__text'><?=$n->text?></div><?php
		if($viewed) :
?>	<label class='msnotification__check'><input type="checkbox" name='ids[]' value='<?=$n->id?>' /></label><?php
		else :
?>	<div class='btn_loader msnotification__spinner'></div><?php
		endif;
?></div><?php
		endforeach;
?></div><?php
	 }

	protected function ViewNotification()
	 {
		if(0 >= ($id = Filter::NumFromPOST('id'))) throw new Exception('Неправильный идентификатор уведомления!');
		$data = ['viewed_at' => DB::Now()];
		DB::UpdateById('sys_notification', $data, $id);
		$data['count'] = MSNotifications::GetUnreadCount();
		self::SendJSON($data);
	 }
}

class MSNotificationsViewer extends MSDocument
{
	use TNotificationsViewer;

	final public function Show()
	 {
		$filter = new MSFButtons(MSLoader::GetUrl(), true);
		$group = $filter->AddGroup('viewed')->AddBtn('Новые', '')->AddBtn('Просмотренные', 'true');
?><div class="msui_filter"><span class="msui_filter__group"><?php
		foreach($group as $b) echo ui::FGroupBtn($b);
?></span></div><?php
		$viewed = 'true' === $group->GetValue();
		$res = MSNotifications::GetList($viewed);
		if(count($res))
		 {
			$this->ShowNotifications($res, $viewed);
			if($viewed) :
?><div class='msnotification_actions'><span class='msnotification_actions__sel_qty'></span><input type='button' class='msui_small_button _icon _delete' value='Удалить' disabled='disabled' /></div><?php
			endif;
		 }
		else print(ui::WarningMsg('Нет '.($viewed ? 'просмотренных' : 'новых').' уведомлений.'));
	 }

	final public function Handle()
	 {
		switch($this->ActionPOST())
		 {
			case 'view_notification': $this->ViewNotification();
			case 'delete_notification':
				if($ids = \Filter::NumArrFromPOST('ids'))
				 {
					\MSNotifications::Delete($ids);
					self::SendJSON(['ids' => $ids], count($ids) > 1 ? 'Уведомления удалены.' : 'Уведомление удалено.');
				 }
				else throw new EDocumentHandle('Не указаны идентификаторы.');
		 }
	 }
}
?>