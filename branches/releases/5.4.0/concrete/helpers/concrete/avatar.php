<?php 
/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
class ConcreteAvatarHelper {

	function getStockAvatars() {
		$f = Loader::helper('file');
		$aDir = $f->getDirectoryContents(DIR_FILES_AVATARS_STOCK);
		return $aDir;			
	}

	function outputUserAvatar($uo, $suppressNone = false, $aspectRatio = 1.0) {	
		if (is_object($uo) && $uo->hasAvatar()) {
			if (file_exists(DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg')) {
				$size = DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
				$src = REL_DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
			} else {
				// legacy
				$size = DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.gif';
				$src = REL_DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.gif';
			}
			if (file_exists($size)) {
				$isize = getimagesize($size);
				$isize[0] = round($isize[0]*$aspectRatio);
				$isize[1] = round($isize[1]*$aspectRatio);
				
				$str = '<img class="u-avatar" src="' . $src . '" width="' . $isize[0] . '" height="' . $isize[1] . '" alt="' . $uo->getUserName() . '" />';
				return $str;
			}
		}
		
		if (!$suppressNone) {
			return $this->outputNoAvatar($aspectRatio);
		}
	}
	
	public function getImagePath($uo,$withNoCacheStr=true) {
		if (!$uo->hasAvatar()) {
			return false;
		}
		
		$cacheStr = "?" . time();
		if (file_exists(DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg')) {
			$base = DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
			$src = REL_DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.jpg';
		} else {
			$base = DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.gif';
			$src = REL_DIR_FILES_AVATARS . '/' . $uo->getUserID() . '.gif';
		}
		if($withNoCacheStr) $src .= $cacheStr;
		if (!file_exists($base)) {
			return "";
		} else {
			return $src;
		}
	}

	
	function outputNoAvatar($aspectRatio = 1.0) {
		$str = '<img class="u-avatar" src="' . AVATAR_NONE . '" width="' . AVATAR_WIDTH*$aspectRatio . '" height="' . AVATAR_HEIGHT*$aspectRatio . '" alt="" />';
		return $str;
	}
	

	function processUploadedAvatar($pointer, $uID) {
		$uHasAvatar = 0;
		$imageSize = getimagesize($pointer);
		$oWidth = $imageSize[0];
		$oHeight = $imageSize[1];
		
		
		$finalWidth = 0;
		$finalHeight = 0;

		// first, if what we're uploading is actually smaller than width and height, we do nothing
		if ($oWidth < AVATAR_WIDTH && $oHeight < AVATAR_HEIGHT) {
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
		} else {
			// otherwise, we do some complicated stuff
			// first, we subtract width and height from original width and height, and find which difference is g$
			$wDiff = $oWidth - AVATAR_WIDTH;
			$hDiff = $oHeight - AVATAR_HEIGHT;
			if ($wDiff > $hDiff) {
				// there's more of a difference between width than height, so if we constrain to width, we sh$
				$finalWidth = AVATAR_WIDTH;
				$finalHeight = $oHeight / ($oWidth / AVATAR_WIDTH);
			} else {
				// more of a difference in height, so we do the opposite
				$finalWidth = $oWidth / ($oHeight / AVATAR_HEIGHT);
				$finalHeight = AVATAR_HEIGHT;
			}
		}
		
		$image = imageCreateTrueColor($finalWidth, $finalHeight);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $white);

		switch($imageSize[2]) {
			case IMAGETYPE_GIF:
				$im = imageCreateFromGIF($pointer);
				break;
			case IMAGETYPE_JPEG:
				$im = imageCreateFromJPEG($pointer);
				break;
			case IMAGETYPE_PNG:
				$im = imageCreateFromPNG($pointer);
				break;
		}
		
		
		$newPath = DIR_FILES_AVATARS . '/' . $uID . '.jpg';
		
		if ($im) {
			$res = imageCopyResampled($image, $im, 0, 0, 0, 0, $finalWidth, $finalHeight, $oWidth, $oHeight);
			if ($res) {
				$res2 = imageJPEG($image, $newPath);
				if ($res2) {
					$uHasAvatar = 1;
				}
			}
		}

		return $uHasAvatar;
	}
	
	function removeAvatar($ui) {
		if (is_object($ui)) {
			$uID = $ui->getUserID();
		} else {
			$uID = $ui;
		}
		$db = Loader::db();
		$db->query("update Users set uHasAvatar = 0 where uID = ?", array($uID));
	}

	function updateUserAvatar($pointer, $uID) {
		$uHasAvatar = $this->processUploadedAvatar($pointer, $uID);
		$db = Loader::db();
		$db->query("update Users set uHasAvatar = {$uHasAvatar} where uID = ?", array($uID));
		return $uHasAvatar;
	}
	
	function updateUserAvatarWithStock($pointer, $uID) {
		if ($pointer != "") {
			if (file_exists(DIR_FILES_AVATARS_STOCK . '/' . $pointer)) {
				$uHasAvatar = $this->processUploadedAvatar(DIR_FILES_AVATARS_STOCK . '/' . $pointer, $uID);
				$db = Loader::db();
				$db->query("update Users set uHasAvatar = {$uHasAvatar} where uID = ?", $uID);
			}
		}
	}

}

?>