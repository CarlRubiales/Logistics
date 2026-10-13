<?php

/*
|--------------------------------------------------------------------------
| Customer Support chatbot buttons
|--------------------------------------------------------------------------
|
| Each entry is a button the buyer sees in the support chat widget.
| - 'label'   : text on the button (also shown as the buyer's message).
| - 'answer'  : the bot's reply when the button is tapped.
| - 'buttons' : (optional) follow-up buttons shown after the answer.
| - 'action'  : (optional) 'human' = tells the buyer to type a message
|               so a support agent can reply.
|
| Replace the examples below with your own buttons and answers.
|
*/

return [

    'greeting' => 'Hi! I am Lumora Assistant. Tap a topic below or type your question.',

    'menu' => [
        [
            'label' => 'What is Lumora?',
            'answer' => 'Lumora is an online beauty marketplace where independent sellers offer skincare, makeup and fragrance, and buyers can shop, chat with sellers and follow their favorite shops.',
        ],
        [
            'label' => 'Who is the developers of Lumora',
            'answer' => 'Lumora was created by the Lumora development team. (Replace this text with the developers\' names.)',
        ],
        [
            'label' => 'I will report a product',
            'answer' => 'Sorry about that! Please type the product name or link, describe what is wrong, and attach a photo or file if you have one. An admin will review your report.',
            'action' => 'report',
        ],
    ],

];