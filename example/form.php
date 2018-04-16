<!DOCTYPE html>
<html>
    <body>
        <form method="POST" action="./payment.php" id="paymentForm">
            <input type="hidden" name="amount" value="200" /> <!-- Replace the value with your transaction amount -->
            <input type="hidden" name="payment_method" value="both" /> <!-- Can be card, account, both -->
            <input type="hidden" name="description" value="I Phone X, 100GB, 32GB RAM" /> <!-- Replace the value with your transaction description -->
            <input type="hidden" name="logo" value="http://brandmark.io/logo-rank/random/apple.png" /> <!-- Replace the value with your logo url -->
            <input type="hidden" name="title" value="Victor Store" /> <!-- Replace the value with your transaction title -->
            <input type="hidden" name="country" value="NG" /> <!-- Replace the value with your transaction country -->
            <input type="hidden" name="currency" value="NGN" /> <!-- Replace the value with your transaction currency -->
            <input type="hidden" name="email" value="fionotollan@yahoo.com" /> <!-- Replace the value with your customer email -->
            <input type="hidden" name="firstname" value="Olufemi" /> <!-- Replace the value with your customer firstname -->
            <input type="hidden" name="lastname"value="Olanipekun" /> <!-- Replace the value with your customer lastname -->
            <input type="hidden" name="phonenumber" value="08098787676" /> <!-- Replace the value with your customer phonenumber -->
            <input type="hidden" name="pay_button_text" value="Complete Payment" /> <!-- Replace the value with the payment button text you prefer -->
            <input type="hidden" name="ref" value="MY_NAME_5a2a7f270ac98" /> <!-- Replace the value with your transaction reference. It must be unique per transaction. You can delete this line if you want one to be generated for you. -->
            <input type="submit" value="Submit" style="display:none;" />
        </form>
        <script type="text/javascript" >
            document.addEventListener("DOMContentLoaded", function(event) {
                document.getElementById("paymentForm").submit();
            });
        </script>
    </body>
</html>
