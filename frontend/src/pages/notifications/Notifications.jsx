import React, { useState, useEffect } from 'react';
import api from '../../services/api';
import { Bell, Check } from 'lucide-react';

const Notifications = () => {
    const [notifications, setNotifications] = useState([]);
    const [loading, setLoading] = useState(true);

    const fetchNotifications = async () => {
        try {
            const res = await api.get('/notifications.php');
            if (res.data.status === 'success') {
                setNotifications(res.data.data);
            }
        } catch (err) {
            console.error(err);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchNotifications();
    }, []);

    const markAsRead = async (recipient_id) => {
        try {
            const res = await api.put('/notifications.php', { recipient_id });
            if (res.data.status === 'success') {
                setNotifications(notifications.map(n => 
                    n.recipient_id === recipient_id ? { ...n, is_read: 1 } : n
                ));
            }
        } catch (err) {
            console.error(err);
        }
    };

    return (
        <div>
            <div className="mb-4">
                <h3 className="fw-bold text-dark mb-1">Notifications</h3>
                <p className="text-muted m-0">Stay updated with system alerts</p>
            </div>

            {loading ? (
                <div className="text-center mt-5"><div className="spinner-border text-primary"></div></div>
            ) : (
                <div className="card border-0 shadow-sm">
                    <div className="list-group list-group-flush rounded">
                        {notifications.length === 0 ? (
                            <div className="text-center text-muted py-5">
                                <Bell size={48} className="mb-3 opacity-50" />
                                <h5>No notifications</h5>
                            </div>
                        ) : (
                            notifications.map(notif => (
                                <div key={notif.recipient_id} className={`list-group-item list-group-item-action p-4 border-0 border-bottom ${notif.is_read ? 'bg-white' : 'bg-light'}`}>
                                    <div className="d-flex w-100 justify-content-between align-items-start">
                                        <div className="d-flex gap-3 align-items-start">
                                            <div className={`p-2 rounded-circle ${notif.is_read ? 'bg-secondary bg-opacity-10 text-secondary' : 'bg-primary bg-opacity-10 text-primary'}`}>
                                                <Bell size={20} />
                                            </div>
                                            <div>
                                                <h6 className={`mb-1 ${notif.is_read ? 'text-muted' : 'fw-bold'}`}>{notif.title}</h6>
                                                <p className="mb-1 small text-secondary">{notif.message}</p>
                                                <small className="text-muted">{new Date(notif.created_at).toLocaleString()}</small>
                                            </div>
                                        </div>
                                        {!notif.is_read && (
                                            <button 
                                                className="btn btn-sm btn-outline-primary rounded-pill d-flex align-items-center gap-1"
                                                onClick={() => markAsRead(notif.recipient_id)}
                                            >
                                                <Check size={14} /> Mark Read
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

export default Notifications;
