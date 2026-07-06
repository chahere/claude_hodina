<?php

namespace App\Controller\Admin;

use App\Entity\HodinaSetting;

final class HodinaSettingCommerceCrudController extends HodinaSettingCrudController
{
    protected const GROUP_KEY_FILTER = HodinaSetting::GROUP_COMMERCE;
}
