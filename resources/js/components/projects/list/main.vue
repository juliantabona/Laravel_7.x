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
    }
</script>
