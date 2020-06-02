<template>

    <Row :gutter="12">
        
        <Col :span="20" :offset="2">

            <Row :gutter="12">

                <Col :span="24">

                    <h1 class="text-center border-bottom-dashed py-3 mb-3">My Projects</h1>

                </Col>

                <Col v-for="(project, index) in projects" :key="index" :span="8">

                    <singleProjectCard :project="project"></singleProjectCard>

                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>
    
    import singleProjectCard from './components/singleProjectCard.vue'; 

    export default {
        components: { singleProjectCard },
        data(){
            return {
                projects: [
                    {
                        name: 'Project 1'
                    },
                    {
                        name: 'Project 2'
                    },
                    {
                        name: 'Project 3'
                    }
                ]
            }
        },
        computed: {
            projectsUrl(){
                return this.user
            }
        },
        methods: {
            navigateTo(projectUrl){
                
                if( projectUrl ){

                    //  Navigate to show the project
                    this.$router.push({ name: 'show-project', params: { url: encodeURIComponent(projectUrl) } });

                }

            },
            fetchProjects() {

                //  If we have the project url
                if( this.projectUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.projectUrl)
                        .then(({data}) => {
                            
                            //  Console log the data returned
                            console.log(data);

                            //  Get the project
                            self.project = data || null;

                            //  Stop loader
                            self.isLoading = false;

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            }
        },
        created(){

            //  Fetch the project
            this.fetchProjects();
            
        }
    }
</script>
