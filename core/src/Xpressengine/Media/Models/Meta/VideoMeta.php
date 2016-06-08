<?php
/**
 * This file is VideoMeta class
 *
 * @category    Media
 * @package     Xpressengine\Media
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Media\Models\Meta;

/**
 * Class VideoMeta
 *
 * @category    Media
 * @package     Xpressengine\Media
 */
class VideoMeta extends Meta
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'files_video';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'fileId'];

    /**
     * The foreign key name for the model.
     *
     * @var string
     */
    protected $foreignKey = 'fileId';
}
