<?php

namespace Example\Services\Examples\eSignature;

use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\CarbonCopy;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;
use DocuSign\eSign\Model\Text;
use DocuSign\eSign\Model\TextCustomField;
use DocuSign\eSign\Model\Recipients;

class aktestService
{
    /**
     * Do the work of the example
     * 1. Create the envelope request object
     * 2. Send the envelope
     *
     * @param  $args array
     * @param $clientService
     * @param $demoDocsPath
     * @return array ['redirect_url']
     */
    # ***DS.snippet.0.start
    public static function signingViaEmail(array $args, $clientService, $demoDocsPath): array
    {
        # 1. Create the envelope request object
        $envelope_definition = static::make_envelope($args["envelope_args"], $clientService, $demoDocsPath); // AK: static.
        $envelope_api = $clientService->getEnvelopeApi();

        # 2. call Envelopes::create API method
        # Exceptions will be caught by the calling function
        try {
            $envelopeResponse = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
        } catch (ApiException $e) {
            $clientService->showErrorTemplate($e);
            exit;
        }

        return ['envelope_id' => $envelopeResponse->getEnvelopeId()];
    }

    /**
     * Creates envelope definition
     * Document 1: An HTML document.
     * Document 2: A Word .docx document.
     * Document 3: A PDF document.
     * DocuSign will convert all of the documents to the PDF format.
     * The recipients' field tags are placed using <b>anchor</b> strings.
     *
     * Parameters for the envelope: signer_email, signer_name, signer_client_id
     *
     * @param  $args array
     * @param $clientService
     * @param $demoDocsPath
     * @return EnvelopeDefinition -- returns an envelope definition
     */
    public static function make_envelope(array $args, $clientService, $demoDocsPath): EnvelopeDefinition
    {
        # document 1 (html) has sign here anchor tag **signature_1**
        # document 2 (docx) has sign here anchor tag /sn1/
        # document 3 (pdf)  has sign here anchor tag /sn1/
        #
        # The envelope has two recipients.
        # recipient 1 - signer
        # recipient 2 - cc
        # The envelope will be sent first to the signer.
        # After it is signed, a copy is sent to the cc person.
        #
        # create the envelope definition
        $envelope_definition = new EnvelopeDefinition([
           'email_subject' => 'Please sign this Hourly Rental Agreement'
        ]);
/*
        $doc1_b64 = base64_encode($clientService->createDocumentForEnvelope($args));

        # read files 2 and 3 from a local directory
        # The reads could raise an exception if the file is not available!
*/
        // $content_bytes = file_get_contents($demoDocsPath . $GLOBALS['DS_CONFIG']['doc_docx']);
        $content_bytes = file_get_contents($demoDocsPath . 'hourly-rental-agreement.docx');
        $doc2_b64 = base64_encode($content_bytes);
/*
        $content_bytes = file_get_contents($demoDocsPath . $GLOBALS['DS_CONFIG']['doc_pdf']);
        $doc3_b64 = base64_encode($content_bytes);

        # Create the document models
        $document1 = new Document([  # create the DocuSign document object
            'document_base64' => $doc1_b64,
            'name' => 'Order acknowledgement',  # can be different from actual file name
            'file_extension' => 'html',  # many different document types are accepted
            'document_id' => '1'  # a label used to reference the doc
        ]);
*/
        $document2 = new Document([  # create the DocuSign document object
            'document_base64' => $doc2_b64,
            'name' => 'Hourly Rental Agreement',  # can be different from actual file name
            'file_extension' => 'docx',  # many different document types are accepted
            'document_id' => '2'  # a label used to reference the doc
        ]);
/*
        $document3 = new Document([  # create the DocuSign document object
            'document_base64' => $doc3_b64,
            'name' => 'Lorem Ipsum',  # can be different from actual file name
            'file_extension' => 'pdf',  # many different document types are accepted
            'document_id' => '3'  # a label used to reference the doc
        ]);
*/
        # The order in the docs array determines the order in the envelope
        $envelope_definition->setDocuments([/*$document1,*/ $document2/*, $document3*/]);

        # Create the signer recipient model
        $signer1 = new Signer([
            'email' => $_POST['signer_email'], 'name' => $_POST['signer_name'],
            'recipient_id' => "1", 'routing_order' => "1"]);

        # routingOrder (lower means earlier) determines the order of deliveries
        # to the recipients. Parallel routing order is supported by using the
        # same integer as the order for two or more recipients.

        # create a cc recipient to receive a copy of the documents
        $cc1 = new CarbonCopy([
            'email' => $_POST['cc_email'], 'name' => $_POST['cc_name'],
            'recipient_id' => "2", 'routing_order' => "2"
        ]);

        // ================================================================================================
        // return SMSDeliveryService::addSignersToTheDelivery($signer1, $cc1, $envelope_definition, $args);
        // ================================================================================================

        # Create signHere fields (also known as tabs) on the documents,
        # We're using anchor (autoPlace) positioning
        #
        # The DocuSign platform searches throughout your envelope's
        # documents for matching anchor strings. So the
        # signHere2 tab will be used in both document 2 and 3 since they
        #  use the same anchor string for their "signer 1" tabs.
/*
        $sign_here1 = new SignHere([
                                       'anchor_string' => '**signature_1**', 'anchor_units' => 'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '20']);
        $sign_here2 = new SignHere([
                                       'anchor_string' => '/sn1/', 'anchor_units' =>  'pixels',
                                       'anchor_y_offset' => '10', 'anchor_x_offset' => '20']);

        # Add the tabs model (including the sign_here tabs) to the signer
        # The Tabs object wants arrays of the different field/tab types
        $signer1->setTabs(new Tabs([
                                       'sign_here_tabs' => [$sign_here1, $sign_here2]]));
*/

        # Add the recipients to the envelope object
        $recipients = new Recipients(['signers' => [$signer1], 'carbon_copies' => [$cc1]]);
        $envelope_definition->setRecipients($recipients);

        # Request that the envelope be sent by setting |status| to "sent".
        # To request that the envelope be created as a draft, set to "created"
        $envelope_definition->setStatus($args["status"]);



        # Create a sign_here tab (field on the document)
        $sign_here = new SignHere([ # DocuSign SignHere field/tab
            'anchor_string' => '/ds-renter-signature/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => 10, 'anchor_x_offset' => 20
        ]);

        // Editable fields (unlocked)
        $text_renter_name = new Text([
            'anchor_string' => '/ds-renter-name/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['signer_name'],
            'locked' => false, 'tab_id' => 'renter_name',
            'tab_label' => 'Renter Name']);

        $text_renter_email = new Text([
            'anchor_string' => '/ds-renter-email/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['signer_email'],
            'locked' => false, 'tab_id' => 'renter_email',
            'tab_label' => 'Renter Email']);

        $text_document_date = new Text([
            'anchor_string' => '/ds-doc-date/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['doc_date'],
            'locked' => false, 'tab_id' => 'doc_date',
            'tab_label' => 'Date of Rental']);


        // Locked fields
        $text_date_of_rental = new Text([
            'anchor_string' => '/ds-date-of-rental/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['date_of_rental'],
            'locked' => true, 'tab_id' => 'date_of_rental',
            'tab_label' => 'Date of Rental']);

        $text_time_slot_of_rental = new Text([
            'anchor_string' => '/ds-time-slot-of-rental/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['time_slot_of_rental'],
            'locked' => true, 'tab_id' => 'time_slot_of_rental',
            'tab_label' => 'Time Slot of Rental']);

        $text_total_due = new Text([
            'anchor_string' => '/ds-total-due/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['total_due'],
            'locked' => true, 'tab_id' => 'total_due',
            'tab_label' => 'Total Due']);

        $text_retainer_fee_due = new Text([
            'anchor_string' => '/ds-retainer-fee-due/', 'anchor_units' => 'pixels',
            'anchor_y_offset' => -7, 'anchor_x_offset' => 3,
            'width' => 440,
            'height' => 40, // AK multiline
            'font' => 'Helvetica', 'font_size' => 'size11',
            'bold' => true, 'value' => $_POST['retainer_fee_due'],
            'locked' => true, 'tab_id' => 'retainer_fee_due',
            'tab_label' => 'Retainer Fee Due']);


        $signer1->settabs(new Tabs([
            'sign_here_tabs' => [$sign_here],
            'text_tabs' => [
                $text_renter_name,
                $text_renter_email,
                $text_document_date,

                $text_date_of_rental,
                $text_time_slot_of_rental,
                $text_total_due,
                $text_retainer_fee_due,
            ]
        ]));

        return $envelope_definition;
    }
    # ***DS.snippet.0.end
}