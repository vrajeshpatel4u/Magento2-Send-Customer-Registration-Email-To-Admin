<?php
declare(strict_types=1);

namespace V4U\SendAdminEmailCustomerRegistration\Observer\Frontend\Customer;

class RegisterSuccess implements \Magento\Framework\Event\ObserverInterface
{
    const XML_PATH_EMAIL_RECIPIENT = 'trans_email/ident_general/email';
    const SENDER_EMAIL  = 'trans_email/ident_general/email';
    const SENDER_NAME   = 'trans_email/ident_general/name'; 
    const IS_ENABLED   = 'adminemailcustomerregistration/adminemail/enable';   
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $customer = $observer->getData('customer');
        $senderEmail = $this->scopeConfig->getValue(self::SENDER_EMAIL, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $senderName = $this->scopeConfig->getValue(self::SENDER_NAME, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $adminEmail = $senderEmail;
        $isEnabled = $this->scopeConfig->getValue(self::IS_ENABLED, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if($isEnabled){
            try 
            {
                $sender = [    
                    'email' => $senderEmail,
                    'name' => $senderName
                ];
                $to_email = $adminEmail; 
                $transport= $this->transportBuilder->setTemplateIdentifier('customer_registration_admin_email')
                        ->setTemplateOptions(
                            [
                                'area' => 'frontend',
                                'store' => $this->storeManager->getStore()->getId()
                            ])
                        ->setTemplateVars([
                                'name'  => $customer->getFirstName().' '.$customer->getLastName(),
                                'email' => $customer->getEmail()
                            ])
                        ->setFrom($sender)
                        ->addTo($to_email,$senderName)
                        ->getTransport();
                $transport->sendMessage();
                $this->inlineTranslation->resume();
            } 
            catch (\Exception $e) 
            {
                \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($e->getMessage());
            }
        }
    }
}
