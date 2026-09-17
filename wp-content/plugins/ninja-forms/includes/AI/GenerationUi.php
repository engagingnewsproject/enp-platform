<?php

namespace NinjaForms\Includes\AI;

/**
 * Builds the dashboard tile and modal markup for AI form generation.
 */
class GenerationUi
{
    /** @var bool */
    private bool $available;

    /** @var int */
    private int $promptLimit;

    /**
     * Initialize the generation UI renderer.
     *
     * @param bool $available   Whether a usable model is available.
     * @param int  $promptLimit Maximum prompt characters.
     * @return void
     */
    public function __construct(bool $available, int $promptLimit)
    {
        $this->available = $available;
        $this->promptLimit = $promptLimit;
    }

    /**
     * Add the first-class generation tile.
     *
     * @param array $templates Existing dashboard templates.
     * @return array
     */
    public function addTile(array $templates): array
    {
        $templates['autogenerate'] = array(
            'id' => 'autogenerate',
            'title' => esc_html__('Build with AI', 'ninja-forms'),
            'template-desc' => esc_html__(
                'Describe the form you need and AI will build it for you. You can add and remove fields as needed.',
                'ninja-forms'
            ),
            'type' => 'autogenerate',
            'icon' => 'nf-ai-sparkle',
            'modal-title' => $this->available
                ? esc_html__('Describe your form', 'ninja-forms')
                : esc_html(ChatUiBootstrap::connectCopy()['connectTitle']),
            'modal-content' => $this->getModalContent(),
        );

        return $templates;
    }

    /**
     * Render the modal content for the tile.
     *
     * @return string
     */
    private function getModalContent(): string
    {
        if (! $this->available) {
            // The connect explanation is written once, in ChatUiBootstrap, and
            // rendered here as markup and in the drawer as localized copy.
            $connect = ChatUiBootstrap::connectCopy();

            return '<div class="modal-template nf-ai-form-builder">'
                . '<p>' . esc_html__('Connect an AI provider to start building forms with AI.', 'ninja-forms') . '</p>'
                . '<p>' . esc_html($connect['connectBody']) . '</p>'
                . '<p>' . esc_html($connect['connectGoogle']) . '</p>'
                . '<div class="actions"><a href="' . esc_url(admin_url('options-connectors.php'))
                . '" class="primary nf-button">' . esc_html($connect['connectAction'])
                . '</a></div></div>';
        }

        $providers = ProviderManager::getAvailableProviders();
        $choice = ProviderManager::resolveModelChoice($providers);
        if (is_wp_error($choice)) {
            $choice = ProviderManager::emptyChoice();
        }

        return '<div class="modal-template nf-ai-form-builder">'
            // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe string.
            . '<p>' . esc_html__('Describe the form you need and AI will build it. You can fine-tune it in the builder afterward.', 'ninja-forms') . '</p>'
            . '<label>' . esc_html__('AI model', 'ninja-forms') . '</label>'
            . $this->renderModelPicker($providers, $choice)
            . '<textarea id="nf-ai-form-prompt" rows="5" data-max-length="'
            . esc_attr($this->promptLimit) . '" aria-describedby="nf-ai-form-count nf-ai-form-error" placeholder="'
            // phpcs:ignore Generic.Files.LineLength.TooLong -- Translator-safe string.
            . esc_attr__('e.g. A catering quote request form: name, email, phone, event date, number of guests, and a message.', 'ninja-forms') . '"></textarea>'
            . '<div class="nf-ai-prompt-meta"><span id="nf-ai-form-count" '
            . 'class="nf-ai-prompt-count" aria-live="polite">'
            /*
             * The same counter string the browser uses (nfi18n.aiPromptCount),
             * so the separator and unit word are translatable and the value
             * does not change wording the moment aiFormGeneration.js takes
             * over the element.
             */
            . esc_html(
                sprintf(
                    /* translators: 1: current character count, 2: maximum characters. */
                    __('%1$s / %2$s characters', 'ninja-forms'),
                    number_format_i18n(0),
                    number_format_i18n($this->promptLimit)
                )
            ) . '</span></div>'
            . '<p class="nf-ai-privacy">'
            . esc_html__('This model stays attached to the form until you choose a different one.', 'ninja-forms')
            . '</p><div id="nf-ai-form-error" role="alert" style="display:none;"></div>'
            . '<div class="actions"><button id="nf-ai-form-generate" class="primary nf-button">'
            . esc_html__('Generate Form', 'ninja-forms') . '</button></div></div>';
    }

    /**
     * Render the exact-model picker shell and serialized state.
     *
     * The shared browser ModelPicker owns option rendering for both the
     * generation modal and builder drawer.
     *
     * @param array $providers Provider/model rows.
     * @param array $choice    Current choice.
     * @return string
     */
    private function renderModelPicker(array $providers, array $choice): string
    {
        $label = ! empty($choice['model'])
            ? $choice['provider_name'] . ' · ' . $choice['model_name']
            : __('Choose a model', 'ninja-forms');

        return '<div class="nf-ai-model-picker" data-providers="'
            . esc_attr(wp_json_encode($providers)) . '">'
            . '<input type="hidden" id="nf-ai-form-provider" value="' . esc_attr($choice['provider']) . '">'
            . '<input type="hidden" id="nf-ai-form-model" value="' . esc_attr($choice['model']) . '">'
            . '<button type="button" class="nf-ai-model-trigger" aria-expanded="false">'
            . '<span class="nf-ai-model-current">' . esc_html($label) . '</span>'
            . '<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></button>'
            . '<div class="nf-ai-model-menu" hidden><input type="search" class="nf-ai-model-search" placeholder="'
            . esc_attr__('Search models', 'ninja-forms') . '">'
            . '<div class="nf-ai-model-list"></div></div></div>';
    }
}
