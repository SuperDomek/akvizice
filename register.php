<?php require_once 'header.php';?>

<script type="text/javascript">
var check = function() {
  if (document.getElementById('pass').value ==
    document.getElementById('pass_check').value) {
        document.getElementById("pass_check").className = document.getElementById("pass_check").className.replace(" error", " green");
  } else {
      if(!document.getElementById("pass_check").className.includes(" error")){
        document.getElementById("pass_check").className = document.getElementById("pass_check").className + " error";
      }
      if(document.getElementById("pass_check").className.includes(" green")){
        document.getElementById("pass_check").className = document.getElementById("pass_check").className.replace(" green", "");
      }
    
  }
}

</script>

<div id="login">
    <form action="user.php" method="POST">
        <input type="hidden" name="type" value="register">
        <label for="name"><b>Login</b></label>
        <input type="text" placeholder="Zadejte uživatelské jméno" name="name" required>
        
        <label for="pass"><b>Heslo</b></label>
        <input type="password" placeholder="Zadejte heslo" name="pass" id="pass" onkeyup="check();" required>

        <label for="pass_check"><b>Heslo znovu</b></label>
        <input type="password" placeholder="Zadejte znovu heslo" name="pass_check" id="pass_check" onkeyup="check();" required>

        <span id='message'></span>
        <br/>
        <!--<label for="remember">Remember me</label>
        <input type="checkbox" checked="checked" name="remember">-->

        <button type="register">Registrovat</button>
        <a href="index.php"><button type="button">Zrušit</button></a>
        
        
        

        <!--<div class="forgot">
            
            <span>Forgot <a href="#">password?</a></span>
        </div>-->
    </form>
</div>

</body>
</html>