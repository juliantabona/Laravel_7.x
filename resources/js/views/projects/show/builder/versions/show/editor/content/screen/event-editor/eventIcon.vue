<template>

    <!-- Event Icon -->
    <Icon v-if="iconType" :type="iconType" :size="size" />

</template>

<script>

    export default {
        props:{
            eventType: {
                type: String,
                default: ''
            },
            size: {
                type: Number,
                default: '20'
            }
        }, 
        data(){
            return {
                
            }
        },
        computed: {
            iconType(){

                var type = null;
                
                if( this.eventType == "API's" ){

                    type = 'ios-planet-outline';

                }else if( this.eventType == 'CRUD API' ){

                    type = 'ios-cloud-upload-outline';

                }else if( this.eventType == 'SMS API' ){

                    type = 'ios-chatboxes-outline';

                }else if( this.eventType == 'Email API' ){

                    type = 'ios-mail-outline';
                    
                }else if( this.eventType == 'Location API' ){

                    type = 'ios-pin-outline';
                    
                }else if( this.eventType == 'Billing API' ){

                    type = 'ios-cash-outline';
                    
                }else if( this.eventType == 'Subcription API' ){

                    type = 'ios-repeat';
                    
                }else if( this.eventType == 'Validation' ){

                    type = 'ios-alert-outline';
                    
                }else if( this.eventType == 'Formatting' ){

                    type = 'ios-brush-outline';
                    
                }else if( this.eventType == 'Local Storage' ){

                    type = 'ios-archive-outline';
                    
                }else if( this.eventType == 'Custom Code' ){

                    type = 'ios-code-working';
                    
                }else if( this.eventType == 'Revisit' ){

                    type = 'ios-repeat';
                    
                }else if( this.eventType == 'Redirect' ){

                    type = 'ios-arrow-round-forward';
                    
                }

                return type;
            }
        }
    }

</script>