<?php

namespace App\Models\GroupShootTraits;

use App\Models\GroupShoot;
use App\Services\PushService;

trait Notifications
{

    /**
     * Push the parent groupshoot owner that someone joining.
     * @internal param PushService $pusher
     */
    protected function pushUserJoined()
    {
        $title = '用户' . $this->owner->push_name . '参与了您发起的群拍';
        PushService::getPusher()
                   ->chooseReceiver($this->parent->owner)
                   ->setTitle($title)
                   ->setBody($title)
                   ->setExtra(['url' => GroupShoot::IOS_JUMP_FROM_NOTIFICATION_TO_GROUPSHOOT . $this->parent->id])
                   ->push();
    }

    /**
     * Push that user joined groupshoots have merged.
     */
    protected function pushGroupShootMergedSuccessfully()
    {
        $title = '您参与的群拍已被合成视频,点击查看';

        PushService::getPusher()
                   ->chooseReceivers($this->parent->joinedUsers())
                   ->setExtra(['url' => GroupShoot::IOS_JUMP_FROM_NOTIFICATION_TO_GROUPSHOOT . $this->parent->id])
                   ->setTitle($title)
                   ->setBody($title)
                   ->push();
    }

    /**
     * Push context based on the groupshoot type.
     * The parent group shoot SHOULD NOT push anything.
     * @return void
     */
    protected function pushAccordingToType()
    {
        if ($this->isParentShoot()) {
            return;
        }
        if ($this->isMergedShoot()) {
            $this->pushGroupShootMergedSuccessfully();
            return;
        }
        if ($this->owner_id == $this->parent->owner_id) {
            return;
        }
        $this->pushUserJoined();
    }
}
