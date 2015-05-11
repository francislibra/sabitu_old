<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

  class Candidato extends MY_Controller {
    
    function __construct(){

      parent::__construct();
      $this->load->model('inscricao_model');
      
      $this->session->set_userdata( 'check_id', $this->candidato_id );
    }
    

    public function index(){
      
      $this->crud->set_table('candidato');
      $this->crud->unset_fields('uid_unifesp');

      $this->output->enable_profiler(TRUE);
      if( $this->processo_seletivo_id ){
        $inscricao_id =  $this->inscricao_model->validate_inscricao_id($this->candidato_id,$this->processo_seletivo_id,$this->processo_seletivo_sigla);
      }
      if (!$this->adm){
         $this->crud->where('id',$this->candidato_id);
         $this->crud->unset_add();
         $this->crud->unset_print();
         $this->crud->unset_export();
         $this->crud->unset_delete();
      }

      $this->form_validation->run();
      $this->crud->set_rules('cpf'      ,'cpf'     ,'required|callback_valid_cpf');
      $this->crud->set_rules('cep'      ,'cep'     ,'required|callback_valid_campo[8]');
      $this->crud->set_rules('foneFixo' ,'foneFixo','required|callback_valid_campo[12]');
      $this->crud->set_rules('foneCel'  ,'foneCel' ,'required|callback_valid_campo[13]');
    
      $this->crud->required_fields('nome' , 'sexo','cpf','rg','email','endereco','bairro','cidade','uf','cep','estadoCivil','foneFixo','FoneCel');
      $this->crud->columns('nome','email','foneFixo','foneCel','dataCadastro');
      
      $this->crud->callback_before_update(array($this, '_prepare_field'));
      $this->crud->callback_before_insert(array($this, '_prepare_field'));
      
      $this->crud->field_type( 'senha', 'hidden');
      $this->crud->field_type( 'dataCadastro', 'hidden');
      
      $data["inscricao_id"] = $this->inscricao_id;
      $this->load->vars($data);
      $this->load->vars($this->crud->render());
      $this->load->view( 'gerenciar');       
    }
    
    public function _prepare_field( $array_post ) {
      if($this->crud->getState() == 'update' || $this->crud->getState() == 'add') {
        $array_post['dataCadastro'] = date('Y-m-d H:i:s');
      }
   
      return $array_post;
    }

    public function after_delete( $primary_key ) {
       $this->session->set_userdata( 'inscricao_id', 0 );
       $this->inscricao_id = 0;
       return $this->db->insert('user_logs', array('candidato_id' => $this->candidato_id,'processo_seletivo_id' => $primary_key,'action'=>'delete', 'data' => date('Y-m-d H:i:s')));
    }


    function valid_campo($campo,$tamanho){
      $CI =& get_instance();
      $CI->form_validation->set_message('valid_campo', 'O %s informado não é válido.');
     
      $campo = preg_replace('/[^0-9]/','',$campo);
     
      if( strlen($campo) != $tamanho || preg_match('/^([0-9])\1+$/', $campo )){
        return false;
      }
      return true;
    }

    function valid_cpf( $cpf ){
        $CI =& get_instance();
        
        $CI->form_validation->set_message('valid_cpf', 'O %s informado não é válido.');

        $cpf = preg_replace('/[^0-9]/','',$cpf);

        if(strlen($cpf) != 11 || preg_match('/^([0-9])\1+$/', $cpf))
        {
            return false;
        }

        // 9 primeiros digitos do cpf
        $digit = substr($cpf, 0, 9);

        // calculo dos 2 digitos verificadores
        for($j=10; $j <= 11; $j++)
        {
            $sum = 0;
            for($i=0; $i< $j-1; $i++)
            {
                $sum += ($j-$i) * ((int) $digit[$i]);
            }

            $summod11 = $sum % 11;
            $digit[$j-1] = $summod11 < 2 ? 0 : 11 - $summod11;
        }
        
        return $digit[9] == ((int)$cpf[9]) && $digit[10] == ((int)$cpf[10]);
    }

  }

