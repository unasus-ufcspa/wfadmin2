  var flagEditar=false;
  var idAbrirEdicao;
  var nomeForm;
  var emailForm;
  var rgForm;
  var phoneForm;
  function editarAdministrador(id, nmUser, dsEmail, nuIdentification, nuCellphone, idUser){
    var idAberto;
    nomeForm=nmUser;
    emailForm=dsEmail;
    rgForm=nuIdentification;
    phoneForm=nuCellphone;
    $(".editarAdmin").each(function(){
      if($(this).is(':visible')){
        flagEditar=true;
        idAberto=$(this).attr('id');
      }
    });
    if(flagEditar==false){
      openEditar(id);
    }else{
      idAbrirEdicao=id;
      closeEditar(idAberto);
    }
  }
  function openEditar(id){
    var $formulario = $("#formEditAdmin");
    document.getElementById("form_NmUser").value = nomeForm;
    document.getElementById("form_DsEmail").value = emailForm;
    document.getElementById("form_NuIdentification").value = rgForm;
    document.getElementById("form_NuCellphone").value = phoneForm;
    $('#closeEditar').click(function(){
       closeEditar(id);
    });
     $("#"+id+".editarAdmin").append( $formulario );
     $formulario.css("display", "block");
     $("#"+id+".editarAdmin").show();
  }

  function closeEditar(id){
    $("#alertEditar").show();
    document.getElementById("sairEditar").onclick = "confirmarFechar("+id+")";
    document.getElementById("sairEditar").onclick = function() { confirmarFechar(id); }
  }

  function cancelarFechar(){
    $("#alertEditar").hide();
    flagEditar=true;
  }
  function confirmarFechar(id){
    $("#alertEditar").hide();
    $("#"+id+".editarAdmin").hide();
    if(flagEditar){
      flagEditar=false;
      openEditar(idAbrirEdicao);
    }
  }
  //FUNÇÕES DA BUSCA
      var fazerBusca = function(){
        $(".editarAdmin").each(function(){
            $(this).hide();
        });
        $(".divAddAdmin").hide();
        var txt = $('#inputPesquisa').val();
        $('.nomeTutor').each(function(){
           if($(this).text().toUpperCase().indexOf(txt.toUpperCase()) == -1){
               $(this).closest('.itemUsuario').hide();
           }
        });
        var itens = document.getElementsByClassName("itemUsuario");
        var flagItens= true;
        for(var i=0; i<itens.length; i++){
          if(itens[i].style.display!="none"){
            flagItens=false;
          }
        }
        if(flagItens){
          document.getElementById("naoEncontrado").style.visibility="visible";
        }else{
          document.getElementById("naoEncontrado").style.visibility="hidden";
        }
      };
      var limparBusca = function(){
        $('.nomeTutor').each(function(){
           $(this).closest('.itemUsuario').show();
        });
      };
      $("#inputPesquisa").keydown(function(e) {
          limparBusca();
          fazerBusca();
      });
      $('#iconLupa').click(function(){
        if($('#inputPesquisa').val()!=""){
          limparBusca();
          fazerBusca();
        }else{
          limparBusca();
          document.getElementById("naoEncontrado").style.visibility="hidden";
        }
      });

  //ADD ADMIN
      function addAdmin(){
        $(".divAddAdmin").show();
      }
      function closeAddAdmin(){
        $(".divAddAdmin").hide();
      }

  //EXCLUIR USER
      function excluirUser(){
        $("#excluirUsuario").show();
      }
      function cancelarExcluir(){
        $("#excluirUsuario").hide();
      }
