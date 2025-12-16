<?php

defined('_JEXEC') or die;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Factory;

class PlgSystemGraphMailer extends CMSPlugin
{
    public function onAfterRoute()
    {
        Log::addLogger([
            'text_file' => 'graphmailer.php',
            'text_entry_format' => '{DATE} {TIME} {PRIORITY} {MESSAGE}'
            ],
            lOG::ALL,
            ['GRAPHMAILER']
        );
        
        Log::add('GraphMailer: plugin chiamato', Log::INFO, 'graphmailer');
        $app   = \Joomla\CMS\Factory::getApplication();
        $input = $app->input;
        $files = $input->files->getArray();
        $attachment = $files['cv'] ?? null;

        $formName = $input->get('chronoform');

        
        if (!$formName) {
            return;
        }
        $session = Factory::getSession();
        $now = time();
        $last = $session->get('graphmailer_last_submit', 0);
        if($now - $last <30){
            Log::add('GraphMailer: rate limit attivo', Log::INFO, 'graphmailer');
            return;
        }

     
        $data = $input->post->getArray();

       
        require_once JPATH_LIBRARIES . '/custom/sendMailGraph.php';
        
        $recipient = '';
        $subject   = '';
        $content   = '';
        if(!empty($data['telefono2'])){
            Log::add('GraphMailer: submission bloccata [HoneyPot] -> '.$formName, Log::INFO, 'graphmailer');
            return;
        }
        $session->set('graphmailer_last_submit', $now);

        switch ($formName) {

            case 'form-homepage':
                if(empty($data['nome'])){
                    Log::add('Plugin GraphMailer [BLOCCATO] - [CAMPI VUOTI]'.json_encode($data), Log::INFO, 'graphmailer');
                    return;
                }
                $recipient = "luca.soldi@mygladix.com";
                $subject   = "Nuovo contatto dal sito - Ragione Sociale: " .($data['ragione']);
                $content   = "Nome " . ($data['nome'] ?? '') . "<br>Email: " . ($data['email'] ?? '') . "<br>Partita Iva: " . ($data['partitaIva'] ?? '') . "<br>Telefono: " . ($data['telefono'] ?? '') . "<br>Budget: " . ($data['budget'] ?? '') . "<br>Servizio: ". 
                ($data['servizio']) . "<br>Richiesta:<br>" . ($data['richiesta']);
                break;

            case 'formpreventivo':
                if(empty($data['nome'])){
                    Log::add('Plugin GraphMailer [BLOCCATO] - [CAMPI VUOTI]'.json_encode($data), Log::INFO, 'graphmailer');
                    return;
                }
                $recipient = "luca.soldi@mygladix.com";
                $subject   = "Richiesta preventivo per " . $data['unit'] . ", " . $data['servizio'];
                $content   = "Da " . ($data['nome'] ?? '') . "<br> Email: " . ($data['email'] ?? '') . "<br>Numero: " . ($data['telefono'] ?? '') . "<br>Budget: " . ($data['budget'] ?? '') . "<br>Richiesta:<br>" . ($data['msg'] ?? '');
                break;

            case 'formlavora':
                if(empty($data['nome'])){
                    Log::add('Plugin GraphMailer [BLOCCATO] - [CAMPI VUOTI]'.json_encode($data), Log::INFO, 'graphmailer');
                    return;
                }
                $recipient = "luca.soldi@mygladix.com";
                $subject   = "Risposta ad offerta di lavoro: ".($data['mansione']);
                $content   = "Da " . ($data['nome'] ?? '') .  ($data['cognome'] ?? '') ."<br>Email: " . ($data['email'] ?? '') . "<br>Telefono: " . ($data['telefono'] ?? '') . "<br>Richiesta:<br>" . ($data['msg'] ?? '');
                break;
                
            case 'form-29-10-2025-14-53-18':
                if(empty($data['nome'])){
                    Log::add('Plugin GraphMailer [BLOCCATO] - [CAMPI VUOTI]'.json_encode($data), Log::INFO, 'graphmailer');
                    return;
                }
                $recipient = "luca.soldi@mygladix.com";
                $subject   = "Nuovo Contatto dal sito per privati";
                $content   = "Da " . ($data['nome'] ?? '') . "<br>Email: " . ($data['email'] ?? '') . "<br>Numero: " . ($data['telefono'] ?? '') . "<br>Budget: " . ($data['budget'] ?? '') . "<br>Servizio: " . ($data['servizio']) . "<br>Richiesta:<br>" . ($data['richiesta'] ?? '');
                break;
                
            case 'briefing':
                if(empty($data['nome'])){
                    Log::add('Plugin GraphMailer [BLOCCATO] - [CAMPI VUOTI]'.json_encode($data), Log::INFO, 'graphmailer');
                    return;
                }
                $recipient = "luca.soldi@mygladix.com";
                $subject   = "Richiesta di Briefing per " . ($data['mansione']);
                $content   = "Da: " .($data['nome'])." ".($data['cognome']). " ".($data['email']).
                             "<br>Per azienda: " .($data['azienda'])."<br>Contatti - Telefono: ".($data['telefono'])." Telefono aziendale: ".($data['telefonoaziendale'])." Sito web: ".($data['sito']).
                             "<br>Tipologia di Business: ".($data['business'])." Fatturato: ".($data['fatturato']).
                             "<br>Budget mensile marketing: ".($data['budgetmensile']). " affidato a: ".($data['marketingaffidato']).
                             "<br>Urgenza: ".($data['urgenza']). " modalità di contatto preferita: ".($data['modalitacontatto']).
                             "<br>Appuntamento: ".($_POST['dataSelezionata'])." ore ".($_POST['oraSelezionata']).
                             "<br>Richiesta:<br>".($data['msg']);
                break;

            default:
                return; // Form non gestito → esci
        }

        try {

            if ($recipient) {
                $result = sendMailGraph($subject, $content, $recipient, $attachment);
            }

        } catch (\Throwable $e) {

            // Log errore in Joomla
            \Joomla\CMS\Log\Log::add(
                'Errore GraphMailer: ' . $e->getMessage(),
                \Joomla\CMS\Log\Log::ERROR,
                'graphmailer'
            );
        }

        return true;
    }
}