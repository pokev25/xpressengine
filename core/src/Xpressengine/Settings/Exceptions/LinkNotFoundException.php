<?php
/**
 * LinkNotFoundException class
 *
 * @category    Settings
 * @package     Xpressengine\Settings
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Settings\Exceptions;

use Xpressengine\Settings\SettingsException;

/**
 * 관리메뉴의 링크를 찾을 수 없을 경우 발생하는 예외
 *
 * @category    Settings
 * @package     Xpressengine\Settings
 */
class LinkNotFoundException extends SettingsException
{
    protected $message = '설정페이지 메뉴가 지정된 route는 name(as)이 지정되어 있거나 Controller action이어야 합니다.';
}
