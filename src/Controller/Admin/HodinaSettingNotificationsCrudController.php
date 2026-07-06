<?php

namespace App\Controller\Admin;

use App\Entity\HodinaSetting;

final class HodinaSettingNotificationsCrudController extends HodinaSettingCrudController
{
    protected const GROUP_KEY_FILTER = HodinaSetting::GROUP_NOTIFICATIONS;
}
