<?php

namespace App\Traits\Eloquent\Model;

use App\Modules\Pub\Document\Models\Document;

trait CanBindDocument
{
    /*** RELATIONS ***/

    public function binded_documents()
    {
        return $this->morphedByMany(Document::class, 'course_bind')->withPivot(['type', 'count']);
    }
}
