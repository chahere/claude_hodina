<?php

namespace App\Controller\Admin;

use App\Entity\HodinaSetting;

final class HodinaSettingGeneralCrudController extends HodinaSettingCrudController
{
    protected const GROUP_KEY_FILTER = HodinaSetting::GROUP_GENERAL;
}
