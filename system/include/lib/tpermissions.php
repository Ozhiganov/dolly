<?php
trait TPermissions
{
	final public function GetPermissions()
	 {
		if(null === $this->curr_permissions) $this->LoadPermissions();
		return $this->curr_permissions;
	 }

	final public function HideMenuItem($id)
	 {
		if(null === $this->curr_permissions) $this->LoadPermissions();
		return empty($this->curr_permissions[MSSiteManagerMeta::Instance()->AliasExists($id, $alias) ? $alias : $id]);
	 }

	final private function LoadPermissions()
	 {
		$this->curr_permissions = [];
		foreach(DB::Select('user_permission', '*', '`suid` = ?', [MSSMAI()->GetSUID()]) as $permit) $this->curr_permissions[$permit->document_id] = $permit->level;
	 }

	private $curr_permissions = null;
}
?>