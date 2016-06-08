<?php
/**
 * RegisterException class. This file is part of the Xpressengine package.
 *
 * @category    Register
 * @package     Xpressengine\Register
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Register;

use Xpressengine\Support\Exceptions\XpressengineException;

/**
 * register 패키지에서 사용되는 exception의 부모클래스
 *
 * @category    Register
 * @package     Xpressengine\Register
 */
class RegisterException extends XpressengineException
{
    protected $message = 'Xpressengine Register package exception';
}
