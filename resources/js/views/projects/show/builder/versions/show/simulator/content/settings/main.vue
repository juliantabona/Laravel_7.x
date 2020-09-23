<template>

    <div>

        <Divider orientation="left">
            <span class="text-primary">Settings</span>
        </Divider>

        <!-- Screen name input (Changes the screen name) -->  
        <Input type="text" v-model="version.builder.simulator.subscriber.phone_number" placeholder="26772334455" maxlength="11" class="mb-3">
                <span slot="prepend">Mobile Number</span>
        </Input>
                    
        <!-- Show first display checkbox (Marks the display as the first display) -->
        <Checkbox v-model="version.builder.simulator.settings.allow_timeouts" class="mb-3">Allow Timeouts</Checkbox>

        <template v-if="version.builder.simulator.settings.allow_timeouts">
            
            <div class="d-flex mb-3">

                <span class="font-weight-bold mt-1 mr-1">Timeout: </span>
                
                <InputNumber v-model.number="version.builder.simulator.settings.timeout_limit_in_seconds" 
                        :min="60" :max="120" placeholder="Timeout limit..." class="w-100">
                </InputNumber>

            </div>

            <Input type="textarea" v-model="version.builder.simulator.settings.timeout_message" class="mb-3"></Input> 

        </template>   

    </div>
    
</template>

<script>

    /*  Loaders  */
    import Loader from'./../../../../../../../../../components/_common/loaders/default.vue';  

    export default {
        components: { Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                selectedLogType: 'All',
            }
        }
    };
  
</script>