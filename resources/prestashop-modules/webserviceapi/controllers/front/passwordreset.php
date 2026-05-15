<?php
require_once dirname(__FILE__) . '/../../classes/MlabFactoryApiBaseModuleFrontController.php';

class webserviceapipasswordresetModuleFrontController extends MlabFactoryApiBaseModuleFrontController
{
    protected function handleRequest()
    {
        $method = strtoupper((string) $_SERVER['REQUEST_METHOD']);

        if ($method === 'POST') {
            return $this->requestPasswordReset();
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            return $this->confirmPasswordReset();
        }

        throw new MlabFactoryApiException('Unsupported HTTP method. Use POST to request reset or PUT to confirm reset.', 405);
    }

    protected function requestPasswordReset()
    {
        $payload = $this->getJsonPayload();
        MlabFactoryApiHelper::requireFields($payload, array('email'));

        $email = trim((string) $payload['email']);
        if (!Validate::isEmail($email)) {
            throw new MlabFactoryApiException('Invalid email address.', 422);
        }

        $customer = MlabFactoryApiHelper::getCustomerByEmail($email);
        if (!$customer || !Validate::isLoadedObject($customer)) {
            return array(
                'message' => 'If the email exists, a password reset link has been sent.',
            );
        }

        if (!(bool) $customer->active) {
            throw new MlabFactoryApiException('Customer account is disabled.', 403);
        }

        if (!$customer->hasRecentResetPasswordToken()) {
            $customer->stampResetPasswordToken();
            if (!$customer->update()) {
                throw new MlabFactoryApiException('Unable to generate reset token.', 500);
            }
        }

        $token = $customer->getValidResetPasswordToken();
        $resetLink = $this->context->link->getPageLink('password', true, (int) $this->context->language->id, array(
            'token' => $token,
            'id_customer' => (int) $customer->id,
            'reset_token' => $token,
        ));

        $templateVars = array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{email}' => $customer->email,
            '{reset_link}' => $resetLink,
            '{reset_token}' => $token,
        );

        if (Mail::Send(
            (int) $this->context->language->id,
            'password_query',
            Mail::l('Password query confirmation'),
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname
        )) {
            return array(
                'message' => 'Password reset email sent successfully.',
                'reset_token' => $token,
            );
        }

        throw new MlabFactoryApiException('Unable to send reset email.', 500);
    }

    protected function confirmPasswordReset()
    {
        $payload = $this->getJsonPayload();
        MlabFactoryApiHelper::requireFields($payload, array('email', 'token', 'new_password'));

        $email = trim((string) $payload['email']);
        $token = trim((string) $payload['token']);
        $newPassword = (string) $payload['new_password'];

        if (!Validate::isEmail($email)) {
            throw new MlabFactoryApiException('Invalid email address.', 422);
        }

        if (empty($token) || !Validate::isSha1($token)) {
            throw new MlabFactoryApiException('Invalid reset token.', 422);
        }

        if (strlen($newPassword) < 5) {
            throw new MlabFactoryApiException('Password must be at least 5 characters long.', 422);
        }

        $customer = MlabFactoryApiHelper::getCustomerByEmail($email);
        if (!$customer || !Validate::isLoadedObject($customer)) {
            throw new MlabFactoryApiException('Invalid reset token or email.', 401);
        }

        $validToken = $customer->getValidResetPasswordToken();
        if (!$validToken || $validToken !== $token) {
            throw new MlabFactoryApiException('Invalid or expired reset token.', 401);
        }

        $customer->passwd = MlabFactoryApiHelper::hashPassword($newPassword);
        $customer->removeResetPasswordToken();

        if (!$customer->update()) {
            throw new MlabFactoryApiException('Unable to update password.', 500);
        }

        return array(
            'message' => 'Password reset successfully.',
            'customer' => MlabFactoryApiHelper::serializeCustomer($customer),
        );
    }
}
