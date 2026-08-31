<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

class UpdateWidgetRequest extends StoreWidgetRequest
{
    // Identical rules: editing a widget re-validates the same payload shape
    // as creating one. The area itself is not editable — a widget belongs to
    // the area it was created for (the edit form keeps it as a hidden input).
}
