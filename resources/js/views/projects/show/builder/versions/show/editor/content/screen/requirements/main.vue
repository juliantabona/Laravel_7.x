<template>

    <div>

        <Row :gutter="12" class="bg-grey-light border mb-3 p-2 clearfix">

            <Col :span="firstRowSpan">
                                
                <!-- Show active state checkbox (Marks if this is active / inactive) -->
                <activeStateSelector 
                    v-model="screen.requirements.requires_account.active" 
                    title="Requires Account:" :titleStyle="{ width: '200px' }">
                </activeStateSelector>

            </Col>

            <Col :span="firstRowSpan">

                <!-- Select Screen Link -->
                <screenAndDisplaySelector 
                    :link="screen.requirements.requires_account.link"
                    :version="version" :screen="screen" :showDisplays="false">
                </screenAndDisplaySelector>

            </Col>

        </Row>

        <Row :gutter="12" class="bg-grey-light border mb-3 p-2 clearfix">

            <Col :span="secondRowSpan">
                                
                <!-- Show active state checkbox (Marks if this is active / inactive) -->
                <activeStateSelector 
                    v-model="screen.requirements.requires_subscription.active" 
                    title="Requires Subscription:" :titleStyle="{ width: '280px' }">
                </activeStateSelector>

            </Col>

            <Col :span="secondRowSpan">

                <!-- Select Screen Link -->
                <screenAndDisplaySelector 
                    :link="screen.requirements.requires_subscription.link"
                    :version="version" :screen="screen" :showDisplays="false">
                </screenAndDisplaySelector>

            </Col>

        </Row>

    </div>
    
</template>

<script>

    import activeStateSelector from './../activeStateSelector.vue';
    import screenAndDisplaySelector from './../screenAndDisplaySelector.vue';

    export default {
        components: { activeStateSelector, screenAndDisplaySelector },
        props: {
            screen: {
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
                
            }
        },
        computed: {
            firstRowSpan(){

                if( this.screen.requirements.requires_account.active.selected_type == 'conditional' ){

                    return 24;

                }

                return 12;

            },
            secondRowSpan(){

                if( this.screen.requirements.requires_subscription.active.selected_type == 'conditional' ){

                    return 24;

                }

                return 12;

            }
        },
    };
  
</script>