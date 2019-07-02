<?php require_once 'header.php'?>



<div id="login">
    <form action="user.php">
        <input type="hidden" name="type" value="login">

        <label for="name"><b>Login</b></label>
        <input type="text" placeholder="Zadejte uživatelské jméno" name="name" required>
        
        <label for="pass"><b>Heslo</b></label>
        <input type="password" placeholder="Zadejte heslo" name="pass" required>
        
        <!--<label for="remember">Remember me</label>
        <input type="checkbox" checked="checked" name="remember">-->

        <button type="submit">Přihlásit</button>
        <!--<button type="button">Cancel</button>-->
        <a href="register.php"><button type="button">Registrovat</button></a>
        
        

        <!--<div class="forgot">
            
            <span>Forgot <a href="#">password?</a></span>
        </div>-->
    </form>
</div>

</body>
</html>