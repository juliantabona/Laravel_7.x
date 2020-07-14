<template>

    <div class="marvel-device nexus5">
        <div class="top-bar"></div>
        <div class="sleep"></div>
        <div class="volume"></div>
        <div class="camera"></div>
        <div class="screen">

            <!-- Homescreen info goes here -->
            <div v-show="!showUssdContentModal" class="homescreen-content">
                
                <Row :gutter="12" class="pt-5">

                    <!-- Screen Heading -->
                    <Col :span="24" class="pr-2 pl-2 pb-2">

                        <Card>

                            <!-- Application Name -->
                            <span class="font-weight-bold text-primary mr-1">{{ applicationName }}</span>
                            
                            <!-- Version Number -->
                            <small>Version: {{ versionNumber }}</small>

                        </Card>

                    </Col>

                    <!-- Screen Content -->
                    <Col :span="24" class="pr-2 pl-2 pb-2">

                        <Card>
                            
                            <span class="font-weight-bold d-block border-bottom-dashed mb-2 pb-2">App Simulator</span>

                            <p class="mt-2">
                                <span class="d-block">
                                    Inform your customers to Dial 
                                    <span v-if="dedicatedShortCode" class="font-weight-bold text-primary">{{ dedicatedShortCode }}</span> 
                                    <span v-if="dedicatedShortCode && sharedShortCode"> or </span> 
                                    <span v-if="sharedShortCode" class="font-weight-bold text-primary">{{ sharedShortCode }}</span> 
                                     to visit the <span class="font-weight-bold text-primary">{{ applicationName }}</span>
                                     app on their mobile phones. Click <span class="font-weight-bold text-primary">Launch Simulator</span> 
                                     to see how your customers view your store.
                                </span>
                            </p>

                            <!-- Launch Simulator button -->
                            <div class="clearfix mt-2">

                                <Poptip trigger="hover" 
                                        class="float-right"
                                        placement="top-end"
                                        word-wrap width="250" 
                                        content="Launch Simulator to have a glimpse of what your customers see when visiting your store">
                                    <Button type="success" size="small" @click.native="launchUssdServiceSimulator()">Launch Simulator</Button>
                                </Poptip>
                            </div>

                        </Card>

                    </Col>

                </Row>

            </div>

            <!-- Ussd info goes here -->
            <div v-show="showUssdContentModal" class="ussd-content-container">
                    
                <Poptip trigger="hover" :content="onlineStatusMsg" word-wrap width="300">
                        
                    <span :class="'ussd-heading' + (isOnline ? ' online' : ' offline')">
                        <span>{{ applicationName }} App is {{ (isOnline ? 'Online' : 'Offline') }}</span>
                    </span>

                </Poptip>

                <Card :bordered="false" class="ussd-content">

                    <div v-show="!isSendingUssdResponse">

                        <!-- Ussd response goes here -->
                        <div>
                            <p v-html="ussdResponse" style="white-space: pre-wrap;"></p>
                        </div>

                        <!-- Ussd reply button -->
                        <Input 
                            ref="reply_input"
                            v-model="ussd.msg" type="text"
                            class="ussd_input w-100 mt-2" size="small"
                            @keyup.enter.native="callUssdEndpoint()"
                            @keyup.escape.native="closeUssdSimulator()">
                        </Input>

                    </div>
                        
                    <!-- Loader -->
                    <Loader v-if="isSendingUssdResponse" class="text-left mt-2">{{ ussdLoaderText }}</Loader>

                    <!-- Send/Cancel buttons -->
                    <div v-if="isSendingUssdResponse == false" class="ussd_reply_container mt-3 d-flex">
                        
                        <Poptip trigger="hover" content="Press ENTER on keyboard" 
                                class="float-right" placement="bottom-end" word-wrap width="220">
                            <span class="ussd_btn font-weight-bold ml-4 text-primary" @click="closeUssdSimulator()">Cancel</span>
                        </Poptip>
                        
                        <template v-if="ussd.requestType == 2">
                        
                            <span class="text-grey-light">|</span>

                            <Poptip trigger="hover" content="Press ESC on keyboard" class="float-right mr-2" 
                                    :placement="isSendingUssdResponse ? 'bottom-end' : 'bottom'" word-wrap width="200">
                                <span class="ussd_btn font-weight-bold mr-4 text-primary" @click="callUssdEndpoint()">Send</span>
                            </Poptip>

                        </template>

                    </div>

                </Card>

                <div v-if="isSendingUssdResponse" class="clearfix mt-1" :style="{ position: 'relative', zIndex: 2 }">

                    <!-- Stop Simulator Button -->
                    <Button type="error" size="small" class="float-right"
                            @click.native="closeUssdSimulator()">
                            Stop Simulator
                    </Button>

                </div>

                <div class="overlay"></div>

            </div>

            <img src="/assets/images/screensaver-01.jpg" style="width:100%;">

        </div>

    </div>

</template>

<script>

    /*  Loaders   */
    import Loader from './../loaders/default.vue'; 

    /*  Buttons  */
    import basicButton from './../buttons/basicButton.vue';

    export default {
        components: { Loader, basicButton },
        props: {
            project:{
                type: Object,
                default: null
            },
            version:{
                type: Object,
                default: null
            },
            ussdLoaderText: {
                type: String,
                default: 'USSD Code running'
            },
            defaultUssdReply: {
                type: String,
                default: ''
            }
        },
        data(){
            return {
                ussd: {
                    msg: this.defaultUssdReply,
                    serviceCode: null,
                    sessionId: null,
                    requestType: 1,
                    msisdn: null
                },
                ussdResponse: '',
                showUssdContentModal: false,
                isSendingUssdResponse: false,
                //  phoneNumber: '+26700000000',
            }
        },
        computed: {
            applicationName(){
                return this.project.name;
            },
            isOnline(){
                return this.project.online;
            },
            onlineStatusMsg(){
                if( this.isOnline ){
                    return 'This means that your '+this.applicationName+' App is Online and can be accessed by your customers using their mobile phones.';
                }else{
                    return 'This means that your '+this.applicationName+' App is Offline and can\'t be accessed by your customers. Turn on Live Mode to allow access for your customers.';
                }
            },
            shortCodeDetails(){
                return this.project['_embedded']['short_code'];
            },
            dedicatedShortCode(){
                return this.shortCodeDetails.dedicated_code;
            },
            sharedShortCode(){
                return this.shortCodeDetails.shared_code;
            },
            primaryShortCode(){
                return this.dedicatedShortCode || this.sharedShortCode;
            },
            versionNumber(){
                return this.version.number;
            },
        },
        methods: {
            updateServiceCode(){

                //  If we have the dedicated or shared short code then use it as the service code
                if( this.primaryShortCode ){
                    this.ussd.serviceCode = this.primaryShortCode;
                }

            },
            showUssdPopup(){
                this.showUssdContentModal = true;
                this.focusOnReplyInput();
            },
            hideUssdPopup(){
                this.showUssdContentModal = false;
            },
            launchUssdServiceSimulator(){
                this.resetUssdSimulator();
                this.callUssdEndpoint();
                this.showUssdPopup();
            },
            closeUssdSimulator(){
                this.resetUssdSimulator();
                this.hideUssdPopup();
            },
            resetUssdSimulator(){
                this.ussd.msg = this.defaultUssdReply;
                this.ussd.sessionId = null;
                this.ussd.requestType = 1;
                this.updateServiceCode();
                this.emptyInput();
            },
            redirectUssdSimulator( serviceCode ){

                //  Reset the Ussd Simulator
                this.resetUssdSimulator();

                //  Update the service code with the redirect service code
                this.ussd.serviceCode = serviceCode;

                //  Recall the Ussd end point
                this.callUssdEndpoint();
                
            },
            emptyInput(){
                this.ussd.msg = '';
            },
            focusOnReplyInput(){

                const self = this;

                this.$nextTick(() => {

                    //  Focus on the reply input field
                    self.$refs.reply_input.$refs.input.focus();

                });


            },
            callUssdEndpoint() {  

                var self = this;

                //  If this is the first request then embbed the service code within the message
                if( this.ussd.requestType == 1 ){

                    this.ussd.msg = this.ussd.serviceCode;

                }

                //  Store data
                let ussdData = {
                    
                    testMode: true,
                    msg: this.ussd.msg,
                    msisdn: this.ussd.msisdn,
                    sessionId: this.ussd.sessionId,
                    requestType: this.ussd.requestType,
                    
                };

                self.$emit('loading', true);

                //  Start loader
                self.isSendingUssdResponse = true;

                //  Use the api call() function located in resources/js/api.js
                return api.call('post', self.project._links['oq:ussd_service_builder'].href, ussdData)
                    .then(({data}) => {

                        //  Stop loader
                        self.isSendingUssdResponse = false;

                        //  Update Ussd Response Message
                        self.ussdResponse = (data || {}).msg;
                        
                        //  Update Ussd Response Type
                        self.ussd.requestType = (data || {}).request_type;
                        
                        //  Update Ussd Session Id
                        self.ussd.sessionId = (data || {}).session_id;

                        //  Update Ussd Service Code
                        self.ussd.serviceCode = (data || {}).service_code;

                        self.emptyInput();

                        self.$emit('response', data);

                        self.$emit('loading', false);

                        //  If the requestType = 2 it means we want to continue the current session 
                        if( self.ussd.requestType == 2 ){

                            //  Focus on the reply input
                            self.focusOnReplyInput();


                        //  If the requestType = 5 it means we want to redirect 
                        }else if( self.ussd.requestType == 5 ){

                            //  Note: self.ussdResponse contains the new "Ussd Service Code" that we must redirect to
                            self.redirectUssdSimulator( self.ussdResponse );

                            //  Focus on the reply input
                            self.focusOnReplyInput();

                        }
                        
                    })         
                    .catch(response => { 
                    
                        console.log(response);

                        self.$emit('loading', false);

                        //  Stop loader
                        self.isSendingUssdResponse = false;     
        
                    });

            }

        },
        created() {

            //  Get and assign the ussd code
            this.updateServiceCode();

        },

    }

</script>