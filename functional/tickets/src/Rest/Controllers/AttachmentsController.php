<?php

namespace Functional\Tickets\Rest\Controllers;

use Functional\Tickets\Rest\Resources\AttachmentResource;
use Lomkit\Rest\Http\Controllers\Controller;
use Lomkit\Rest\Http\Resource;

class AttachmentsController extends Controller
{
    /**
     * The resource the controller corresponds to.
     *
     * @var class-string<\Lomkit\Rest\Http\Resource>
     */
    public static $resource = AttachmentResource::class;
}
