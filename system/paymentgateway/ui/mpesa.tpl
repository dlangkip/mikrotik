{include file="sections/header.tpl"}

<form class="form-horizontal" method="post" role="form" action="{$_url}paymentgateway/mpesa">
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="panel panel-primary panel-hovered panel-stacked mb30">
                <div class="panel-heading">{Lang::T('M-Pesa Payment Gateway')}</div>
                <div class="panel-body">
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Key</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_consumer_key" name="mpesa_consumer_key"
                                value="{$_c['mpesa_consumer_key']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Consumer Secret</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_consumer_secret" name="mpesa_consumer_secret"
                                value="{$_c['mpesa_consumer_secret']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Shortcode</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_shortcode" name="mpesa_shortcode"
                                value="{$_c['mpesa_shortcode']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Passkey</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="mpesa_passkey" name="mpesa_passkey"
                                value="{$_c['mpesa_passkey']}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Environment</label>
                        <div class="col-md-6">
                            <select class="form-control" name="mpesa_environment">
                                <option value="sandbox" {if $_c['mpesa_environment'] == 'sandbox'}selected{/if}>Sandbox</option>
                                <option value="live" {if $_c['mpesa_environment'] == 'live'}selected{/if}>Live</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label">Callback URL</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="callback_url" readonly
                                value="{$_url}callback/mpesa">
                            <span class="help-block">Copy this URL and set it as your callback URL in the M-Pesa developer portal</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary waves-effect waves-light" type="submit">{Lang::T('Save Changes')}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading">{Lang::T('M-Pesa Integration Instructions')}</div>
            <div class="panel-body">
                <ol>
                    <li>Sign up for an M-Pesa Daraja account at <a href="https://developer.safaricom.co.ke/" target="_blank">https://developer.safaricom.co.ke/</a></li>
                    <li>Create a new app in the Daraja portal and obtain your Consumer Key and Consumer Secret</li>
                    <li>Set up your M-Pesa Shortcode and Passkey in the Daraja portal</li>
                    <li>Configure the Callback URL in the Daraja portal using the URL provided above</li>
                    <li>Enter all the required information in the form above</li>
                    <li>Choose the appropriate environment (Sandbox for testing, Live for production)</li>
                    <li>Save the changes and test the integration</li>
                </ol>
                <p>For more detailed instructions, please refer to the <a href="https://developer.safaricom.co.ke/docs" target="_blank">M-Pesa API documentation</a>.</p>
            </div>
        </div>
    </div>
</div>

{include file="sections/footer.tpl"}
