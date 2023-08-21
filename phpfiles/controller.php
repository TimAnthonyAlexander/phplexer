<?php

namespace t17r\CoallaApi\controller\AcceptInvite;

use t17r\CoallaApi\controller\Base\BaseController;
use t17r\CoallaApi\model\Project\ProjectMemberModel;
use t17r\CoallaApi\model\User\UserLogModel;

class AcceptInviteController extends BaseController
{
    public string $project;

    public function getAction(): void
    {
        $this->wrongMethod();
    }

    public function postAction(): void
    {
        $projectMember = ProjectMemberModel::getRequestsForUser($this->userState->userModel);

        foreach ($projectMember as $value) {
            assert($value instanceof ProjectMemberModel);
            if ($value->project === $this->project) {
                if ($value->isInvited && !$value->isVerified) {
                    $value->isVerified = true;
                    $value->save();

                    UserLogModel::log(
                        $this->userId,
                        UserLogModel::ACTION_JOIN_PROJECT,
                        $this->project,
                    );

                    return;
                }
            }
        }

        $this->status = 404;
    }
}
