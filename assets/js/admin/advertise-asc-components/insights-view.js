import React, {useState} from 'react';
import { Card, Form, Space, Spin, Switch, Table, Tooltip, Typography} from 'antd';
import { QuestionCircleFilled } from '@ant-design/icons'

function UpdateState(callback) {
    // do the update
    setTimeout(callback, 3000);
   // callback();
}

const FunnelComponentView = (props) => {

    const sum = props.reach + props.clicks + props.views + props.addToCarts + props.purchases;
    return (
        <table class="bar-chart">
            <thead style={{height:'90%'}}>
                <th></th>
                <th><div style={{height: 200.0 * (props.reach/sum) + 'px'}}></div></th>
                <th><div style={{height: 200.0 * (props.clicks/sum) + 'px'}}></div></th>
                <th><div style={{height: 200.0 * (props.views/sum) + 'px'}}></div></th>
                <th><div style={{height: 200.0 * (props.addToCarts/sum) + 'px'}}></div></th>
                <th><div style={{height: 200.0 * (props.purchases/sum) + 'px'}}></div></th>
            </thead>
            <tbody style={{height:'10%'}}>
                <tr>
                    <td><Tooltip title="'X-through rate, Cost per action' "><QuestionCircleFilled style={{ fontSize: '75%', alignContent: 'center' }} /></Tooltip></td>
                    <td><label>Reach {props.reach}</label></td>
                    <td><label>Clicks {props.clicks}</label></td>
                    <td><label>Views {props.views}</label></td>
                    <td><label>Add to cart {props.addToCarts}</label></td>
                    <td><label>Purchase {props.purchases}</label></td>
                </tr>
            </tbody>
        </table>
    );
}

const CampaignDetailsComponentView = (props) => {
  
    const {Text} = Typography;

    const [loading, setLoading] = useState(false);
    const [state, setState] = useState(props.status);

    const onStatusChange = (newValue) => {
        setState(newValue);
        setLoading(true);
        UpdateState( () => {setLoading(false)});
    };

    const columns = [
        {
            title: 'Status',
            dataIndex: 'status',
            key: 'status',
        },
        {
            title: 'Spend',
            dataIndex: 'spend',
            key: 'spend',
        },
        {
            title: 'Daily Budget',
            dataIndex: 'dailyBudget',
            key: 'dailyBudget',
        }
    ];

    const values = [
        {
            key: '1',
            spend: props.spend,
            dailyBudget: props.dailyBudget, 
            status: (
                <Spin spinning={loading}>
                    <Form.Item>
                        <Switch checked={state}
                            checkedChildren="On"
                            unCheckedChildren="Off"
                            onChange={(e) => { onStatusChange(e);}} />
                    </Form.Item>
                </Spin>
            ),
        }
    ];

    return (
        <Form>
            <Space direction='vertical' style={{width:'100%'}}>
                <Table pagination={false} columns={columns} dataSource={values} />
                <FunnelComponentView reach={props.reach} clicks={props.clicks} views={props.views} addToCarts={props.addToCarts} purchases={props.purchases} />
            </Space>
        </Form>
    );
};

const InsightsView = (props) => {
    return (
        <Card>
            <CampaignDetailsComponentView spend={0} dailyBudget={0} reach={123} clicks={80} views={50} addToCarts={25} purchases={18} status={true}/>
        </Card>
    );
}

export default InsightsView;