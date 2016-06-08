<?php
/**
 * This file is invalid credentials exception.
 *
 * @category    User
 * @package     Xpressengine\User
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\User\Exceptions;

use Xpressengine\User\UserException;

/**
 * 로그인을 위한 계정정보가 유효하지 않을 때 발생하는 Exception
 *
 * @category    User
 * @package     Xpressengine\User
 */
class InvalidCredentialsException extends UserException
{

}
