<?php

return [
    'bet_amount' => 'integer|min:10',
    'selected_numbers' => 'string|required|selected_numbers_format',
    'rambolito' => 'boolean|nullable',
    'advance_draws' => 'boolean|nullable|consecutive_draws_required_with_advance_draws',
    'consecutive_draws' => 'integer|max:6',
    'lucky_pick' => 'boolean|nullable',
];
