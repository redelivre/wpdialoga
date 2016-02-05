<?php
class DialogaAPI 
{
    // url to Dialoga api
    protected $apiUrl = 'http://login.dialoga.gov.br/api/v1/';
     
    public function getAllProposals()
    {
        // first url - get by articles 
        //$json = wp_remote_get($this->apiUrl . 'communities/19195/articles?content_type=ProposalsDiscussionPlugin::Proposal');
        // second url - get by articles
        //$json = wp_remote_get($this->apiUrl . 'communities/19195/articles?content_type=ProposalsDiscussionPlugin::Proposal');

        // TODO: insert total of elements - 88 for pagination - 9 pages. See information on header Total.

        // main dev - get by proposals TODO: test with apiUrl
        //  $request = wp_remote_get($this->apiUrl . 'proposals_discussion_plugin/121501/ranking?page=' . $page  . '&per_page=10');
        $request = wp_remote_get($this->apiUrl . 'proposals_discussion_plugin/121501/ranking?page=1&per_page=10');
        $response = wp_remote_retrieve_body( $request );
        
        $proposals = array();
        if(is_object(json_decode($response)))
        {	
        	$proposals = json_decode($response)->{'proposals'};
        }
        else 
        {
        	var_dump($response); //TODO error report
        }
        
     
        // only for development without connection 
        //$response = file_get_contents(dirname(__FILE__).'/dialoga_proposals.json');
        //$proposals = json_decode($response)->{'proposals'};
         
        if(!is_array($proposals))
        {
        	var_dump($proposals); //TODO error report
        	
        	$proposals = array();
        }
        
        return $proposals;
    }
}
