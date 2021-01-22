<?php
/**
 * Plugin Name: Kaizen Css
 * Plugin URI: https://firedev.com.br
 * Author: FireDev
 * Author URI: https://firedev.com.br
 * Description: Plugin para integração dos formulários Contact Form 7 com a ferramenta CSS de captura de leads
 * Version: 0.1.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 */

require_once 'vendor/autoload.php';

global $cssHttpClient;
$cssHttpClient = new \Firedev\KaizenCSS\Client();

add_action( 'wp_enqueue_scripts', 'adicionaScriptIntegracao' );

function adicionaScriptIntegracao(){
    wp_enqueue_script( 'integration-script', plugin_dir_url( __FILE__ ) . 'js/script.js');
}

add_action('wpcf7_mail_sent', 'integracaoAPICssCF7');

function integracaoAPICssCF7($contactForm)
{
    global $cssHttpClient;

    $submission = WPCF7_Submission::get_instance();

    if ($submission) {
        $data = $submission->get_posted_data();
        foreach ($data as $key => $value) {
            if (strpos($key, "_wpcf7") !== false) {
                continue;
            }

            if ($key === 'nome') {
                $nome = $value;
                continue;
            }

            if ($key === 'e_mail') {
                $email = $value;
                continue;
            }

            if ($key === 'telefone') {
                $telefone = $value;
                continue;
            }

            if ($key === 'mensagem') {
                $mensagem = $value;
                continue;
            }

            $additionalData[] = [
                'key'        => $key,
                'title'      => ucfirst($key),
                'value'      => $value,
                'searchable' => 1
            ];
        }
    }

    $cssHttpClient->registraLead($nome, $email, $telefone, $mensagem, $additionalData);
}

add_action('caldera_forms_submit_complete', 'integracaoAPICssCalderaForms');

function integracaoAPICssCalderaForms($form)
{
    global $cssHttpClient;

    $fields = Caldera_Forms_Forms::get_fields($form);

    $nome     = "";
    $email    = "";
    $telefone = "";
    $mensagem = "";
    $additionalData = [];

    foreach ($fields as $field) {
        // Não enviar no payload para o css o valor do botão submit, apenas os dados do lead
        if ($field['config']['type'] === 'submit') {
            continue;
        }

        if ($field['slug'] === 'nome') {
            $nome = Caldera_Forms::get_field_data($field['ID'], $form);
            continue;
        }

        if ($field['slug'] === 'e_mail') {
            $email = Caldera_Forms::get_field_data($field['ID'], $form);
            continue;
        }

        if ($field['slug'] === 'telefone') {
            $telefone = Caldera_Forms::get_field_data($field['ID'], $form);
            continue;
        }

        if ($field['slug'] === 'mensagem') {
            $mensagem = Caldera_Forms::get_field_data($field['ID'], $form);
            continue;
        }

        $additionalData[] = [
            'key'        => $field['slug'],
            'title'      => $field['label'],
            'value'      => Caldera_Forms::get_field_data($field['ID'], $form),
            'searchable' => 1
        ];
    }

    $cssHttpClient->registraLead($nome, $email, $telefone, $mensagem, $additionalData);
}